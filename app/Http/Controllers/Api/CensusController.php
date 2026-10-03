<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CensusCampaign;
use App\Models\CensusSubmission;
use App\Models\SalaryGrid;
use App\Support\CameroonCivilServiceGrid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Recensement
 */
class CensusController extends Controller
{
    /**
     * État actuel du recensement pour l'employé connecté.
     */
    public function current(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => "Aucune fiche employé n'est liée à ce compte."], 404);
        }

        $campaign = CensusCampaign::currentlyOpen();

        if (!$campaign) {
            $ended = CensusCampaign::recentlyEnded();

            if ($ended) {
                return response()->json([
                    'campaign' => null,
                    'message' => "La campagne « {$ended->name} » est terminée depuis le {$ended->ends_at->format('d/m/Y à H:i')}. Contactez les RH si vous n'avez pas pu la compléter à temps.",
                ]);
            }

            return response()->json(['campaign' => null]);
        }

        $submission = CensusSubmission::where('census_campaign_id', $campaign->id)
            ->where('employee_id', $employee->id)
            ->first();

        $employee->loadMissing(['dependents', 'diplomas', 'tradeBody', 'qualification']);

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'ends_at' => $campaign->ends_at?->toIso8601String(),
            ],
            'submission_status' => $submission?->status,
            'stage_label' => $submission?->stage_label,
            'rejection_reason' => $submission?->rejection_reason_for_employee,
            'current_data' => [
                'personal' => [
                    'photo_url' => $employee->photo ? Storage::url($employee->photo) : null,
                    'matricule_fonction_publique' => $employee->matricule_fonction_publique,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'gender' => $employee->gender,
                    'birth_date' => $employee->birth_date?->format('Y-m-d'),
                    'marital_status' => $employee->marital_status,
                    'children_under_6' => $employee->children_under_6,
                    'total_children' => $employee->total_children,
                    'id_card_number' => $employee->id_card_number,
                    'recruitment_date' => $employee->recruitment_date?->format('Y-m-d'),
                    'service_start_date' => $employee->service_start_date?->format('Y-m-d'),
                    'phone' => $employee->phone,
                    'email' => $employee->email,
                    'address' => $employee->address,
                    'city' => $employee->city,
                    'bank_name' => $employee->bank_name,
                    'bank_account_number' => $employee->bank_account_number,
                    'cnps_number' => $employee->cnps_number,
                ],

                // Classification salariale : le TYPE (cameroon/numeric) est fixé
                // administrativement et non modifiable ici. L'employé choisit sa
                // catégorie/échelon parmi les options valides pour ce type ; l'indice
                // est calculé automatiquement (jamais saisi directement).
                'salary_classification' => [
                    'classification_type' => $employee->classification_type,
                    'current_category_number' => $employee->category_number,
                    'current_echelon_number' => $employee->echelon_number,
                    'current_indice' => $employee->indice,
                    'category_options' => $employee->classification_type === 'cameroon'
                        ? CameroonCivilServiceGrid::categoryOptions()
                        : array_combine(range(1, 12), range(1, 12)),
                    // Pour 'cameroon', les échelons valides dépendent de la catégorie
                    // choisie (H.ECH n'existe que pour A2) — fourni par catégorie.
                    'echelon_options_by_category' => $employee->classification_type === 'cameroon'
                        ? collect(CameroonCivilServiceGrid::categoryOptions())
                        ->mapWithKeys(fn($label, $cat) => [$cat => CameroonCivilServiceGrid::echelonOptions($cat)])
                        : collect(range(1, 12))->mapWithKeys(fn($cat) => [(string) $cat => array_combine(range(1, 12), range(1, 12))]),
                    // Table complète (catégorie, échelon, indice) pour ce type de
                    // classification — permet à l'app mobile/web de calculer et afficher
                    // l'indice en direct dès que l'employé choisit catégorie + échelon,
                    // sans aller-retour serveur supplémentaire.
                    'grid_rows' => SalaryGrid::where('classification_type', $employee->classification_type)
                        ->where('is_active', true)
                        ->get(['category', 'echelon', 'indice'])
                        ->map(fn($row) => ['category' => $row->category, 'echelon' => $row->echelon, 'indice' => $row->indice]),
                ],

                // "current_department"/"current_service" : affichés à titre indicatif
                // uniquement, jamais appliqués (structure d'affectation pas encore
                // convertie en liste déroulante — à faire évoluer plus tard si besoin).
                //
                // Les 5 champs suivants (trade_body, qualification, job_title,
                // personnel_type, administrative_status) sont en revanche appliqués
                // réellement à la validation finale — voir "classification_options"
                // ci-dessous pour les listes de choix correspondantes.
                'organizational' => [
                    'current_department' => $employee->department?->name ?? $employee->currentService?->department?->name ?? null,
                    'current_service' => $employee->currentService?->name ?? $employee->service?->name,
                    'current_job_title_id' => $employee->job_title_id,
                    'current_trade_body_id' => $employee->trade_body_id,
                    'current_qualification_id' => $employee->qualification_id,
                    'current_personnel_type' => $employee->personnel_type,
                    'current_administrative_status' => $employee->administrative_status,
                ],

                'classification_options' => [
                    'trade_bodies' => \App\Models\TradeBody::orderBy('name')->get(['id', 'name']),
                    'qualifications' => \App\Models\Qualification::orderBy('name')->get(['id', 'name']),
                    'job_titles' => \App\Models\JobTitle::orderBy('name')->get(['id', 'name']),
                    'personnel_types' => [
                        ['value' => 'soignant', 'label' => 'Soignant'],
                        ['value' => 'non_soignant', 'label' => 'Non Soignant'],
                        ['value' => 'paramedical', 'label' => 'Paramédical'],
                        ['value' => 'autres', 'label' => 'Autres'],
                    ],
                    'administrative_statuses' => [
                        ['value' => 'fonctionnaire_affecte', 'label' => 'Fonctionnaire Affecté'],
                        ['value' => 'fonctionnaire_detache', 'label' => 'Fonctionnaire Détaché'],
                        ['value' => 'contractuel_structure', 'label' => 'Contractuel de la Structure'],
                    ],
                ],
                'dependents' => $employee->dependents->map(fn($d) => [
                    'id' => $d->id,
                    'relationship' => $d->relationship,
                    'first_name' => $d->first_name,
                    'last_name' => $d->last_name,
                    'birth_date' => $d->birth_date?->format('Y-m-d'),
                    'birth_place' => $d->birth_place,
                    'gender' => $d->gender,
                    'phone' => $d->phone,
                    'email' => $d->email,
                    'address' => $d->address,
                ]),
                'diplomas' => $employee->diplomas->map(fn($d) => [
                    'id' => $d->id,
                    'type' => $d->type,
                    'title' => $d->title,
                    'institution' => $d->institution,
                    'year_obtained' => $d->year_obtained,
                ]),
            ],
        ]);
    }

    /**
     * Soumettre (ou re-soumettre) le recensement complet.
     */
    public function submit(Request $request, int $campaignId)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => "Aucune fiche employé n'est liée à ce compte."], 404);
        }

        $campaign = CensusCampaign::find($campaignId);

        if (!$campaign || !$campaign->isCurrentlyAccessible()) {
            return response()->json(['message' => "Cette campagne de recensement n'est plus ouverte."], 404);
        }

        $existing = CensusSubmission::where('census_campaign_id', $campaign->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existing && !$existing->canBeResubmitted()) {
            return response()->json(['message' => 'Ce recensement a déjà été validé et ne peut plus être modifié.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'personal' => ['required', 'array'],
            'personal.matricule_fonction_publique' => ['nullable', 'string', 'max:255'],
            'personal.category_number' => ['nullable', 'string', 'max:255'],
            'personal.echelon_number' => ['nullable', 'string', 'max:255'],
            'personal.first_name' => ['nullable', 'string', 'max:255'],
            'personal.last_name' => ['nullable', 'string', 'max:255'],
            'personal.gender' => ['nullable', 'in:M,F'],
            'personal.birth_date' => ['nullable', 'date'],
            'personal.marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'personal.children_under_6' => ['nullable', 'integer', 'min:0'],
            'personal.total_children' => ['nullable', 'integer', 'min:0'],
            'personal.id_card_number' => ['nullable', 'string', 'max:255'],
            'personal.recruitment_date' => ['nullable', 'date'],
            'personal.service_start_date' => ['nullable', 'date'],
            'personal.phone' => ['nullable', 'string', 'max:255'],
            'personal.email' => ['nullable', 'email', 'max:255'],
            'personal.address' => ['nullable', 'string', 'max:255'],
            'personal.city' => ['nullable', 'string', 'max:255'],
            'personal.bank_name' => ['nullable', 'string', 'max:255'],
            'personal.bank_account_number' => ['nullable', 'string', 'max:255'],
            'personal.cnps_number' => ['nullable', 'string', 'max:255'],

            'photo' => ['nullable', 'image', 'max:4096'],

            'organizational' => ['nullable', 'array'],
            // Département/service : toujours déclaratif, texte libre, jamais appliqué.
            'organizational.declared_department' => ['nullable', 'string', 'max:255'],
            'organizational.declared_service' => ['nullable', 'string', 'max:255'],
            // Les 5 champs suivants sont désormais appliqués réellement à la validation
            // finale (voir CensusValidationService) — validés par ID/valeur fermée pour
            // garantir une correspondance correcte avec les vraies tables.
            'organizational.declared_job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
            'organizational.declared_trade_body_id' => ['nullable', 'integer', 'exists:trade_bodies,id'],
            'organizational.declared_qualification_id' => ['nullable', 'integer', 'exists:qualifications,id'],
            'organizational.declared_personnel_type' => ['nullable', 'in:soignant,non_soignant,paramedical,autres'],
            'organizational.declared_administrative_status' => ['nullable', 'in:fonctionnaire_affecte,fonctionnaire_detache,contractuel_structure'],

            'dependents' => ['array'],
            'dependents.*.existing_id' => ['nullable', 'integer'],
            'dependents.*.relationship' => ['required_with:dependents', 'in:spouse,child,father,mother'],
            'dependents.*.last_name' => ['required_with:dependents', 'string', 'max:255'],
            'dependents.*.first_name' => ['nullable', 'string', 'max:255'],
            'dependents.*.birth_date' => ['required_with:dependents', 'date'],
            'dependents.*.birth_place' => ['nullable', 'string', 'max:255'],
            'dependents.*.gender' => ['required_with:dependents', 'in:M,F'],
            'dependents.*.phone' => ['nullable', 'string', 'max:255'],
            'dependents.*.email' => ['nullable', 'email', 'max:255'],
            'dependents.*.address' => ['nullable', 'string'],

            'diplomas' => ['array'],
            'diplomas.*.existing_id' => ['nullable', 'integer'],
            'diplomas.*.type' => ['required_with:diplomas', 'in:recruitment_diploma,highest_diploma,training'],
            'diplomas.*.title' => ['required_with:diplomas', 'string', 'max:255'],
            'diplomas.*.institution' => ['required_with:diplomas', 'string', 'max:255'],
            'diplomas.*.year_obtained' => ['required_with:diplomas', 'integer', 'min:1950', 'max:' . now()->format('Y')],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        unset($data['photo']); // traité séparément ci-dessous, pas stocké tel quel dans le payload

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('census/profile-photos', 'public');
        }

        // Indice calculé côté serveur (jamais confié au client) à partir du type de
        // classification FIXE de l'employé + de la catégorie/échelon qu'il vient de
        // choisir. Stocké dans le payload pour affichage immédiat côté RH.
        $category = $data['personal']['category_number'] ?? null;
        $echelon = $data['personal']['echelon_number'] ?? null;

        if ($category && $echelon) {
            $data['personal']['computed_indice'] = SalaryGrid::lookupIndice(
                $employee->classification_type,
                $category,
                $echelon
            );
        }

        foreach ($data['dependents'] ?? [] as $index => &$dependent) {
            foreach (['photo', 'id_card', 'birth_certificate', 'marriage_certificate'] as $docKey) {
                $file = $request->file("dependents.{$index}.{$docKey}");
                if ($file) {
                    $dependent['documents'][$docKey] = $file->store('census/dependents', 'public');
                }
            }

            if (empty($dependent['existing_id']) && empty($dependent['documents']['birth_certificate'] ?? null)) {
                return response()->json([
                    'message' => "Un acte de naissance est requis pour chaque nouvel ayant droit (position {$index}).",
                ], 422);
            }
        }
        unset($dependent);

        foreach ($data['diplomas'] ?? [] as $index => &$diploma) {
            $file = $request->file("diplomas.{$index}.document");
            if ($file) {
                $diploma['document_path'] = $file->store('census/diplomas', 'public');
            }

            if (empty($diploma['existing_id']) && empty($diploma['document_path'] ?? null)) {
                return response()->json([
                    'message' => "Un document justificatif est requis pour chaque nouveau diplôme (position {$index}).",
                ], 422);
            }
        }
        unset($diploma);

        $submission = CensusSubmission::updateOrCreate(
            ['census_campaign_id' => $campaign->id, 'employee_id' => $employee->id],
            [
                'status' => CensusSubmission::STATUS_SUBMITTED,
                'payload' => $data,
                'submitted_at' => now(),
                'career_validated_by' => null,
                'career_validated_at' => null,
                'career_rejection_reason' => null,
                'solde_validated_by' => null,
                'solde_validated_at' => null,
                'solde_rejection_reason' => null,
                'validated_by' => null,
                'validated_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]
        );

        return response()->json([
            'message' => 'Recensement soumis, en attente de validation.',
            'status' => $submission->status,
        ], 201);
    }
}
