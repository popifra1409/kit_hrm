<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CensusCampaign;
use App\Models\CensusSubmission;
use Illuminate\Http\Request;
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

        $employee->loadMissing(['dependents', 'diplomas']);

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'ends_at' => $campaign->ends_at?->toIso8601String(),
            ],
            'submission_status' => $submission?->status,
            'rejection_reason' => $submission?->isRejected() ? $submission->rejection_reason : null,
            'current_data' => [
                'personal' => [
                    'phone' => $employee->phone,
                    'email' => $employee->email,
                    'address' => $employee->address,
                    'city' => $employee->city,
                    'bank_name' => $employee->bank_name,
                    'bank_account_number' => $employee->bank_account_number,
                    'cnps_number' => $employee->cnps_number,
                ],
                'organizational' => [
                    'current_department' => $employee->department?->name ?? $employee->currentService?->department?->name ?? null,
                    'current_service' => $employee->currentService?->name ?? $employee->service,
                    'current_job_title' => $employee->jobTitle?->name ?? $employee->job_title,
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

        if ($existing && $existing->isValidated()) {
            return response()->json(['message' => 'Ce recensement a déjà été validé et ne peut plus être modifié.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'personal' => ['required', 'array'],
            'personal.phone' => ['nullable', 'string', 'max:255'],
            'personal.email' => ['nullable', 'email', 'max:255'],
            'personal.address' => ['nullable', 'string', 'max:255'],
            'personal.city' => ['nullable', 'string', 'max:255'],
            'personal.bank_name' => ['nullable', 'string', 'max:255'],
            'personal.bank_account_number' => ['nullable', 'string', 'max:255'],
            'personal.cnps_number' => ['nullable', 'string', 'max:255'],

            'organizational' => ['nullable', 'array'],
            'organizational.declared_department' => ['nullable', 'string', 'max:255'],
            'organizational.declared_service' => ['nullable', 'string', 'max:255'],
            'organizational.declared_job_title' => ['nullable', 'string', 'max:255'],

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
                'status' => 'submitted',
                'payload' => $data,
                'submitted_at' => now(),
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
