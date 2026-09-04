<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Profil Employé
 */
class EmployeeProfileController extends Controller
{
    /**
     * Consulter ma fiche employé
     *
     * Retourne l'ensemble des informations de l'employé connecté :
     * identité, affectation, classification, coordonnées.
     *
     * @response 200 scenario="Profil trouvé" {
     *   "employee": {
     *     "id": 12,
     *     "matricule": "98240812A",
     *     "full_name": "DUPONT Jean",
     *     "first_name": "Jean",
     *     "last_name": "DUPONT",
     *     "gender": "M",
     *     "birth_date": "1990-05-12",
     *     "photo_url": "https://.../storage/employees/photos/12.jpg",
     *     "phone": "699000000",
     *     "email": "jean.dupont@example.com",
     *     "address": "Yaoundé",
     *     "city": "Yaoundé",
     *     "department": "Médecine Interne",
     *     "service": "Cardiologie",
     *     "sector": null,
     *     "trade_body": "Médecin",
     *     "qualification": "Médecin Généraliste",
     *     "job_title": "Employé",
     *     "administrative_status": "fonctionnaire_affecte",
     *     "administrative_status_label": "Fonctionnaire Affecté",
     *     "category_number": "5",
     *     "echelon_number": "3",
     *     "recruitment_date": "2015-03-01",
     *     "is_active": true
     *   }
     * }
     * @response 404 scenario="Aucun employé lié" {"message": "Aucune fiche employé n'est liée à ce compte."}
     */
    public function show(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => "Aucune fiche employé n'est liée à ce compte.",
            ], 404);
        }

        return response()->json([
            'employee' => $this->formatEmployee($employee),
        ]);
    }

    /**
     * Mettre à jour mes coordonnées
     *
     * Seuls le téléphone, l'email, l'adresse et la ville sont modifiables
     * par l'employé. Les informations RH/structurelles (service, catégorie,
     * échelon, qualification...) sont réservées aux Ressources Humaines.
     *
     * @bodyParam phone string Numéro de téléphone. Example: 699000000
     * @bodyParam email string Adresse email. Example: jean.dupont@example.com
     * @bodyParam address string Adresse postale/domicile. Example: Yaoundé, Bastos
     * @bodyParam city string Ville. Example: Yaoundé
     *
     * @response 200 scenario="Mise à jour réussie" {"message": "Coordonnées mises à jour.", "employee": {"...": "..."}}
     * @response 404 scenario="Aucun employé lié" {"message": "Aucune fiche employé n'est liée à ce compte."}
     * @response 422 scenario="Validation échouée" {"message": "Données invalides.", "errors": {"email": ["Cette adresse email est déjà utilisée."]}}
     */
    public function update(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => "Aucune fiche employé n'est liée à ce compte.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:employees,email,' . $employee->id],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee->fill($validator->validated());
        $employee->save();

        return response()->json([
            'message' => 'Coordonnées mises à jour.',
            'employee' => $this->formatEmployee($employee->fresh()),
        ]);
    }

    /**
     * Mettre à jour ma photo de profil
     *
     * @bodyParam photo file required Image JPG/PNG, 2 Mo maximum.
     *
     * @response 200 scenario="Photo mise à jour" {"message": "Photo mise à jour.", "photo_url": "https://.../storage/employees/photos/12.jpg"}
     * @response 404 scenario="Aucun employé lié" {"message": "Aucune fiche employé n'est liée à ce compte."}
     * @response 422 scenario="Validation échouée" {"message": "Données invalides.", "errors": {"photo": ["Le fichier doit être une image."]}}
     */
    public function updatePhoto(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => "Aucune fiche employé n'est liée à ce compte.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Supprimer l'ancienne photo si elle existe
        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }

        $path = $request->file('photo')->store('employees/photos', 'public');

        $employee->photo = $path;
        $employee->save();

        return response()->json([
            'message' => 'Photo mise à jour.',
            'photo_url' => Storage::disk('public')->url($path),
        ]);
    }

    private function formatEmployee($employee): array
    {
        $employee->loadMissing([
            'department',
            'currentService',
            'sector',
            'tradeBody',
            'qualification',
            'jobTitle',
        ]);

        return [
            'id' => $employee->id,
            'matricule' => $employee->matricule,
            'full_name' => $employee->full_name,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'gender' => $employee->gender,
            'birth_date' => $employee->birth_date?->format('Y-m-d'),
            'photo_url' => $employee->photo ? Storage::disk('public')->url($employee->photo) : null,

            // Coordonnées (modifiables par l'employé)
            'phone' => $employee->phone,
            'email' => $employee->email,
            'address' => $employee->address,
            'city' => $employee->city,

            // Affectation (lecture seule pour l'employé)
            'department' => $employee->department?->name,
            'service' => $employee->currentService?->name,
            'sector' => $employee->sector?->name,
            'trade_body' => $employee->tradeBody?->name,
            'qualification' => $employee->qualification?->name,
            'job_title' => $employee->jobTitle?->name,

            // Classification (lecture seule)
            'administrative_status' => $employee->administrative_status,
            'administrative_status_label' => $employee->administrative_status_label,
            'category_number' => $employee->category_number,
            'echelon_number' => $employee->echelon_number,
            'recruitment_date' => $employee->recruitment_date?->format('Y-m-d'),
            'is_active' => $employee->is_active,
        ];
    }
}
