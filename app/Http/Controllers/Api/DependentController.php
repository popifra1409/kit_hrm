<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dependent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Ayants Droit
 */
class DependentController extends Controller
{
    /**
     * Lister mes ayants droit
     *
     * @response 200 scenario="Liste" {
     *   "dependents": [
     *     {
     *       "id": 5,
     *       "relationship": "child",
     *       "relationship_label": "Enfant",
     *       "full_name": "Marie Dupont",
     *       "birth_date": "2018-02-10",
     *       "age": 8,
     *       "validation_status": "pending",
     *       "validation_status_label": "En attente de validation",
     *       "rejection_reason": null,
     *       "photo_url": null
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => "Aucune fiche employé n'est liée à ce compte.",
            ], 404);
        }

        $dependents = $employee->dependents()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'dependents' => $dependents->map(fn($d) => $this->formatDependent($d)),
        ]);
    }

    /**
     * Voir le détail d'un ayant droit
     *
     * @response 200 scenario="Détail" {"dependent": {"...": "..."}}
     * @response 404 scenario="Introuvable" {"message": "Ayant droit introuvable."}
     */
    public function show(Request $request, int $id)
    {
        $dependent = $this->findOwnDependent($request, $id);

        if (!$dependent) {
            return response()->json(['message' => 'Ayant droit introuvable.'], 404);
        }

        return response()->json(['dependent' => $this->formatDependent($dependent, full: true)]);
    }

    /**
     * Déclarer un nouvel ayant droit
     *
     * Le dossier est créé avec le statut "En attente" ; les RH le valideront
     * après vérification des documents physiques (pièces jointes envoyées ici
     * servent de première vérification, l'original physique reste à présenter).
     *
     * @bodyParam relationship string required spouse, child, father ou mother. Example: child
     * @bodyParam first_name string Prénom(s).
     * @bodyParam last_name string required Nom.
     * @bodyParam birth_date string required Date de naissance (YYYY-MM-DD).
     * @bodyParam birth_place string Lieu de naissance.
     * @bodyParam gender string required M ou F.
     * @bodyParam phone string Téléphone.
     * @bodyParam email string Email.
     * @bodyParam address string Adresse.
     * @bodyParam photo file Photo (image).
     * @bodyParam id_card_path file Carte d'identité (PDF/image).
     * @bodyParam birth_certificate_path file required Acte de naissance (PDF/image).
     * @bodyParam marriage_certificate_path file Acte de mariage — requis si relationship = spouse.
     *
     * @response 201 scenario="Créé" {"message": "Ayant droit déclaré, en attente de validation par les RH.", "dependent": {"...": "..."}}
     * @response 422 scenario="Validation échouée" {"message": "Données invalides.", "errors": {"birth_certificate_path": ["Ce champ est obligatoire."]}}
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => "Aucune fiche employé n'est liée à ce compte.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'relationship' => ['required', 'in:spouse,child,father,mother'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:M,F'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'id_card_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'birth_certificate_path' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'marriage_certificate_path' => [
                $request->input('relationship') === 'spouse' ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['employee_id'] = $employee->id;
        $data['validation_status'] = 'pending';
        $data['submitted_via'] = 'mobile';
        $data['is_active'] = false; // activé par les RH seulement après validation

        foreach (['photo' => 'photo_path', 'id_card_path' => 'id_card_path', 'birth_certificate_path' => 'birth_certificate_path', 'marriage_certificate_path' => 'marriage_certificate_path'] as $inputName => $column) {
            if ($request->hasFile($inputName)) {
                $data[$column] = $request->file($inputName)->store('dependents/documents', 'public');
            }
        }
        unset($data['photo']);

        $dependent = Dependent::create($data);

        return response()->json([
            'message' => 'Ayant droit déclaré, en attente de validation par les RH.',
            'dependent' => $this->formatDependent($dependent, full: true),
        ], 201);
    }

    /**
     * Modifier un ayant droit (uniquement tant qu'il est en attente)
     *
     * Une fois validé ou rejeté par les RH, la fiche n'est plus modifiable
     * par l'employé — contactez les Ressources Humaines pour toute correction.
     *
     * @response 200 scenario="Modifié" {"message": "Ayant droit mis à jour.", "dependent": {"...": "..."}}
     * @response 403 scenario="Non modifiable" {"message": "Cet ayant droit a déjà été traité par les RH et ne peut plus être modifié."}
     * @response 404 scenario="Introuvable" {"message": "Ayant droit introuvable."}
     */
    public function update(Request $request, int $id)
    {
        $dependent = $this->findOwnDependent($request, $id);

        if (!$dependent) {
            return response()->json(['message' => 'Ayant droit introuvable.'], 404);
        }

        if (!$dependent->isPending()) {
            return response()->json([
                'message' => 'Cet ayant droit a déjà été traité par les RH et ne peut plus être modifié.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'birth_place' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'photo' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'id_card_path' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'birth_certificate_path' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'marriage_certificate_path' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        foreach (['photo' => 'photo_path', 'id_card_path' => 'id_card_path', 'birth_certificate_path' => 'birth_certificate_path', 'marriage_certificate_path' => 'marriage_certificate_path'] as $inputName => $column) {
            if ($request->hasFile($inputName)) {
                if ($dependent->{$column}) {
                    Storage::disk('public')->delete($dependent->{$column});
                }
                $data[$column] = $request->file($inputName)->store('dependents/documents', 'public');
            }
        }
        unset($data['photo']);

        $dependent->fill($data);
        $dependent->save();

        return response()->json([
            'message' => 'Ayant droit mis à jour.',
            'dependent' => $this->formatDependent($dependent->fresh(), full: true),
        ]);
    }

    /**
     * Retirer un ayant droit (uniquement tant qu'il est en attente)
     *
     * @response 200 scenario="Supprimé" {"message": "Ayant droit retiré."}
     * @response 403 scenario="Non supprimable" {"message": "Cet ayant droit a déjà été traité par les RH et ne peut plus être supprimé."}
     */
    public function destroy(Request $request, int $id)
    {
        $dependent = $this->findOwnDependent($request, $id);

        if (!$dependent) {
            return response()->json(['message' => 'Ayant droit introuvable.'], 404);
        }

        if (!$dependent->isPending()) {
            return response()->json([
                'message' => 'Cet ayant droit a déjà été traité par les RH et ne peut plus être supprimé.',
            ], 403);
        }

        $dependent->delete();

        return response()->json(['message' => 'Ayant droit retiré.']);
    }

    private function findOwnDependent(Request $request, int $id): ?Dependent
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return null;
        }

        return $employee->dependents()->where('id', $id)->first();
    }

    private function formatDependent(Dependent $dependent, bool $full = false): array
    {
        $base = [
            'id' => $dependent->id,
            'relationship' => $dependent->relationship,
            'relationship_label' => $dependent->getRelationshipLabel(),
            'full_name' => $dependent->full_name,
            'birth_date' => $dependent->birth_date?->format('Y-m-d'),
            'age' => $dependent->age,
            'gender' => $dependent->gender,
            'validation_status' => $dependent->validation_status,
            'validation_status_label' => $dependent->validation_status_label,
            'rejection_reason' => $dependent->rejection_reason,
            'photo_url' => $dependent->photo_path ? Storage::disk('public')->url($dependent->photo_path) : null,
        ];

        if ($full) {
            $base += [
                'birth_place' => $dependent->birth_place,
                'phone' => $dependent->phone,
                'email' => $dependent->email,
                'address' => $dependent->address,
                'is_active' => $dependent->is_active,
                'coverage_rate' => $dependent->coverage_rate,
                'card_number' => $dependent->card_number,
                'card_issued' => $dependent->card_issued,
                'card_active' => $dependent->card_active,
                'documents' => [
                    'id_card' => $dependent->id_card_path ? Storage::disk('public')->url($dependent->id_card_path) : null,
                    'birth_certificate' => $dependent->birth_certificate_path ? Storage::disk('public')->url($dependent->birth_certificate_path) : null,
                    'marriage_certificate' => $dependent->marriage_certificate_path ? Storage::disk('public')->url($dependent->marriage_certificate_path) : null,
                ],
            ];
        }

        return $base;
    }
}
