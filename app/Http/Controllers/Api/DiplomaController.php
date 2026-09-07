<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDiploma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Diplômes & Formations
 */
class DiplomaController extends Controller
{
    /**
     * Lister mes diplômes et formations
     *
     * @response 200 scenario="Liste" {
     *   "diplomas": [
     *     {
     *       "id": 3,
     *       "type": "recruitment_diploma",
     *       "type_label": "Diplôme de Recrutement",
     *       "title": "Licence en Sciences Infirmières",
     *       "institution": "Université de Yaoundé I",
     *       "year_obtained": 2014,
     *       "validation_status": "pending",
     *       "validation_status_label": "En attente de validation",
     *       "rejection_reason": null,
     *       "document_url": "https://.../storage/employees/diplomas/xxx.pdf"
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

        $diplomas = $employee->diplomas()->orderBy('year_obtained', 'desc')->get();

        return response()->json([
            'diplomas' => $diplomas->map(fn($d) => $this->formatDiploma($d)),
        ]);
    }

    /**
     * Déclarer un diplôme ou une formation
     *
     * Un seul "diplôme de recrutement" et un seul "diplôme le plus élevé"
     * peuvent exister par employé (hors ceux rejetés, qui peuvent être
     * re-soumis). Les formations sont illimitées. Le dossier est créé avec
     * le statut "En attente" ; les RH le valideront après vérification du
     * document physique original.
     *
     * @bodyParam type string required recruitment_diploma, highest_diploma ou training. Example: training
     * @bodyParam title string required Intitulé du diplôme/de la formation. Example: Certification ITIL Foundation
     * @bodyParam institution string required École, université ou organisme. Example: PECB
     * @bodyParam year_obtained integer required Année d'obtention. Example: 2022
     * @bodyParam document file required Document justificatif (PDF ou image, 5 Mo max).
     *
     * @response 201 scenario="Créé" {"message": "Diplôme déclaré, en attente de validation par les RH.", "diploma": {"...": "..."}}
     * @response 409 scenario="Doublon" {"message": "Vous avez déjà un diplôme de recrutement en attente ou validé. Modifiez-le ou attendez son traitement."}
     * @response 422 scenario="Validation échouée" {"message": "Données invalides.", "errors": {"document": ["Ce champ est obligatoire."]}}
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
            'type' => ['required', 'in:' . implode(',', [
                EmployeeDiploma::TYPE_RECRUITMENT,
                EmployeeDiploma::TYPE_HIGHEST,
                EmployeeDiploma::TYPE_TRAINING,
            ])],
            'title' => ['required', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'year_obtained' => ['required', 'integer', 'min:1950', 'max:' . now()->format('Y')],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type');

        // Un seul diplôme de recrutement / plus élevé actif (pending ou validated) à la fois
        if (in_array($type, [EmployeeDiploma::TYPE_RECRUITMENT, EmployeeDiploma::TYPE_HIGHEST])) {
            $existing = $employee->diplomas()
                ->where('type', $type)
                ->whereIn('validation_status', ['pending', 'validated'])
                ->first();

            if ($existing) {
                $label = $type === EmployeeDiploma::TYPE_RECRUITMENT ? 'diplôme de recrutement' : 'diplôme le plus élevé';
                return response()->json([
                    'message' => "Vous avez déjà un {$label} en attente ou validé. Modifiez-le ou attendez son traitement.",
                ], 409);
            }
        }

        $documentPath = $request->file('document')->store('employees/diplomas', 'public');

        $diploma = EmployeeDiploma::create([
            'employee_id' => $employee->id,
            'type' => $type,
            'title' => $request->input('title'),
            'institution' => $request->input('institution'),
            'year_obtained' => $request->input('year_obtained'),
            'document_path' => $documentPath,
            'validation_status' => 'pending',
            'submitted_via' => 'mobile',
        ]);

        return response()->json([
            'message' => 'Diplôme déclaré, en attente de validation par les RH.',
            'diploma' => $this->formatDiploma($diploma),
        ], 201);
    }

    /**
     * Modifier un diplôme/formation (uniquement tant qu'en attente ou rejeté)
     *
     * @response 200 scenario="Modifié" {"message": "Mis à jour.", "diploma": {"...": "..."}}
     * @response 403 scenario="Non modifiable" {"message": "Ce diplôme a déjà été validé et ne peut plus être modifié."}
     */
    public function update(Request $request, int $id)
    {
        $diploma = $this->findOwnDiploma($request, $id);

        if (!$diploma) {
            return response()->json(['message' => 'Diplôme introuvable.'], 404);
        }

        if ($diploma->isValidated()) {
            return response()->json([
                'message' => 'Ce diplôme a déjà été validé et ne peut plus être modifié.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'institution' => ['sometimes', 'required', 'string', 'max:255'],
            'year_obtained' => ['sometimes', 'required', 'integer', 'min:1950', 'max:' . now()->format('Y')],
            'document' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        unset($data['document']);

        if ($request->hasFile('document')) {
            if ($diploma->document_path) {
                Storage::disk('public')->delete($diploma->document_path);
            }
            $data['document_path'] = $request->file('document')->store('employees/diplomas', 'public');
        }

        // Une modification après un rejet relance le circuit de validation
        if ($diploma->isRejected()) {
            $data['validation_status'] = 'pending';
            $data['rejection_reason'] = null;
        }

        $diploma->fill($data);
        $diploma->save();

        return response()->json([
            'message' => 'Mis à jour.',
            'diploma' => $this->formatDiploma($diploma->fresh()),
        ]);
    }

    /**
     * Retirer un diplôme/formation (uniquement tant qu'il n'est pas validé)
     *
     * @response 200 scenario="Supprimé" {"message": "Diplôme retiré."}
     * @response 403 scenario="Non supprimable" {"message": "Ce diplôme a déjà été validé et ne peut plus être supprimé."}
     */
    public function destroy(Request $request, int $id)
    {
        $diploma = $this->findOwnDiploma($request, $id);

        if (!$diploma) {
            return response()->json(['message' => 'Diplôme introuvable.'], 404);
        }

        if ($diploma->isValidated()) {
            return response()->json([
                'message' => 'Ce diplôme a déjà été validé et ne peut plus être supprimé.',
            ], 403);
        }

        $diploma->delete();

        return response()->json(['message' => 'Diplôme retiré.']);
    }

    private function findOwnDiploma(Request $request, int $id): ?EmployeeDiploma
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return null;
        }

        return $employee->diplomas()->where('id', $id)->first();
    }

    private function formatDiploma(EmployeeDiploma $diploma): array
    {
        return [
            'id' => $diploma->id,
            'type' => $diploma->type,
            'type_label' => $diploma->type_label,
            'title' => $diploma->title,
            'institution' => $diploma->institution,
            'year_obtained' => $diploma->year_obtained,
            'validation_status' => $diploma->validation_status,
            'validation_status_label' => $diploma->validation_status_label,
            'rejection_reason' => $diploma->rejection_reason,
            'document_url' => $diploma->document_path ? Storage::disk('public')->url($diploma->document_path) : null,
        ];
    }
}
