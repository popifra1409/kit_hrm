<?php

namespace App\Services;

use App\Models\CensusSubmission;
use App\Models\Dependent;
use App\Models\EmployeeDiploma;
use App\Models\SalaryGrid;
use Illuminate\Support\Facades\DB;

class CensusValidationService
{
    // ========================================
    // ÉTAPE 1 — CARRIÈRE
    // ========================================

    public function validateCareer(CensusSubmission $submission, int $validatorId): void
    {
        $submission->update([
            'status' => CensusSubmission::STATUS_CAREER_VALIDATED,
            'career_validated_by' => $validatorId,
            'career_validated_at' => now(),
        ]);
    }

    public function rejectCareer(CensusSubmission $submission, int $validatorId, string $reason): void
    {
        $submission->update([
            'status' => CensusSubmission::STATUS_CAREER_REJECTED,
            'career_validated_by' => $validatorId,
            'career_validated_at' => now(),
            'career_rejection_reason' => $reason,
        ]);
    }

    // ========================================
    // ÉTAPE 2 — SOLDE
    // ========================================

    public function validateSolde(CensusSubmission $submission, int $validatorId): void
    {
        $submission->update([
            'status' => CensusSubmission::STATUS_SOLDE_VALIDATED,
            'solde_validated_by' => $validatorId,
            'solde_validated_at' => now(),
        ]);
    }

    public function rejectSolde(CensusSubmission $submission, int $validatorId, string $reason): void
    {
        $submission->update([
            'status' => CensusSubmission::STATUS_SOLDE_REJECTED,
            'solde_validated_by' => $validatorId,
            'solde_validated_at' => now(),
            'solde_rejection_reason' => $reason,
        ]);
    }

    // ========================================
    // ÉTAPE 3 — CONTRÔLE FINAL ADMINISTRATEUR
    // ========================================

    /**
     * Validation finale : applique réellement les informations personnelles
     * (dont catégorie/échelon/indice et matricule fonction publique), bancaires/
     * CNPS, la photo, les ayants droit, les diplômes, ainsi que corps de métier/
     * qualification/poste/type de personnel/statut administratif. Seuls
     * département et service restent volontairement déclaratifs — toujours
     * affichés pour vérification manuelle par les RH contre les archives (leur
     * structure d'affectation n'est pas encore convertie en liste déroulante).
     */
    public function apply(CensusSubmission $submission, int $validatorId): void
    {
        DB::transaction(function () use ($submission, $validatorId) {
            $employee = $submission->employee;
            $payload = $submission->payload;
            $personal = $payload['personal'] ?? [];

            $employee->fill(array_filter([
                'matricule_fonction_publique' => $personal['matricule_fonction_publique'] ?? null,
                'first_name' => $personal['first_name'] ?? null,
                'last_name' => $personal['last_name'] ?? null,
                'gender' => $personal['gender'] ?? null,
                'birth_date' => $personal['birth_date'] ?? null,
                'marital_status' => $personal['marital_status'] ?? null,
                'children_under_6' => $personal['children_under_6'] ?? null,
                'total_children' => $personal['total_children'] ?? null,
                'id_card_number' => $personal['id_card_number'] ?? null,
                'recruitment_date' => $personal['recruitment_date'] ?? null,
                'service_start_date' => $personal['service_start_date'] ?? null,
                'phone' => $personal['phone'] ?? null,
                'email' => $personal['email'] ?? null,
                'address' => $personal['address'] ?? null,
                'city' => $personal['city'] ?? null,
                'bank_name' => $personal['bank_name'] ?? null,
                'bank_account_number' => $personal['bank_account_number'] ?? null,
                'cnps_number' => $personal['cnps_number'] ?? null,
            ], fn($v) => $v !== null));

            // Catégorie/échelon/indice : recalculés à CE moment précis (pas de simple
            // recopie de la valeur soumise), au cas où la grille salariale aurait changé
            // entre la soumission de l'employé et la validation finale.
            if (!empty($personal['category_number']) && !empty($personal['echelon_number'])) {
                $employee->category_number = $personal['category_number'];
                $employee->echelon_number = $personal['echelon_number'];
                $employee->indice = SalaryGrid::lookupIndice(
                    $employee->classification_type,
                    $personal['category_number'],
                    $personal['echelon_number']
                );
            }

            if (!empty($payload['photo_path'])) {
                $employee->photo = $payload['photo_path'];
            }

            // Corps de métier / Qualification / Poste / Type de personnel / Statut
            // administratif : désormais appliqués réellement (contrairement à
            // département/service, qui restent purement déclaratifs pour l'instant).
            $organizational = $payload['organizational'] ?? [];

            if (!empty($organizational['declared_trade_body_id'])) {
                $employee->trade_body_id = $organizational['declared_trade_body_id'];
            }
            if (!empty($organizational['declared_qualification_id'])) {
                $employee->qualification_id = $organizational['declared_qualification_id'];
            }
            if (!empty($organizational['declared_job_title_id'])) {
                $employee->job_title_id = $organizational['declared_job_title_id'];
            }
            if (!empty($organizational['declared_personnel_type'])) {
                $employee->personnel_type = $organizational['declared_personnel_type'];
            }
            if (!empty($organizational['declared_administrative_status'])) {
                $employee->administrative_status = $organizational['declared_administrative_status'];
            }

            $employee->save();

            // Ayants droit
            foreach ($payload['dependents'] ?? [] as $item) {
                $documents = $item['documents'] ?? [];

                $data = [
                    'employee_id' => $employee->id,
                    'relationship' => $item['relationship'],
                    'first_name' => $item['first_name'] ?? null,
                    'last_name' => $item['last_name'],
                    'birth_date' => $item['birth_date'],
                    'birth_place' => $item['birth_place'] ?? null,
                    'gender' => $item['gender'],
                    'phone' => $item['phone'] ?? null,
                    'email' => $item['email'] ?? null,
                    'address' => $item['address'] ?? null,
                    'validation_status' => 'validated',
                    'submitted_via' => 'census',
                    'is_active' => true,
                ];

                foreach (['photo' => 'photo_path', 'id_card' => 'id_card_path', 'birth_certificate' => 'birth_certificate_path', 'marriage_certificate' => 'marriage_certificate_path'] as $key => $column) {
                    if (!empty($documents[$key])) {
                        $data[$column] = $documents[$key];
                    }
                }

                if (!empty($item['existing_id'])) {
                    $dependent = Dependent::where('id', $item['existing_id'])
                        ->where('employee_id', $employee->id)
                        ->first();

                    if ($dependent) {
                        $dependent->update($data);
                        continue;
                    }
                }

                Dependent::create($data);
            }

            // Diplômes & formations
            foreach ($payload['diplomas'] ?? [] as $item) {
                $data = [
                    'employee_id' => $employee->id,
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'institution' => $item['institution'],
                    'year_obtained' => $item['year_obtained'],
                    'validation_status' => 'validated',
                    'is_verified' => true,
                    'verified_by' => $validatorId,
                    'verified_at' => now(),
                    'submitted_via' => 'census',
                ];

                if (!empty($item['document_path'])) {
                    $data['document_path'] = $item['document_path'];
                }

                if (!empty($item['existing_id'])) {
                    $diploma = EmployeeDiploma::where('id', $item['existing_id'])
                        ->where('employee_id', $employee->id)
                        ->first();

                    if ($diploma) {
                        $diploma->update($data);
                        continue;
                    }
                }

                EmployeeDiploma::create($data);
            }

            $submission->update([
                'status' => CensusSubmission::STATUS_VALIDATED,
                'validated_by' => $validatorId,
                'validated_at' => now(),
            ]);
        });
    }

    public function reject(CensusSubmission $submission, int $validatorId, string $reason): void
    {
        $submission->update([
            'status' => CensusSubmission::STATUS_REJECTED,
            'validated_by' => $validatorId,
            'validated_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function getUnaddressedDependents(CensusSubmission $submission): \Illuminate\Support\Collection
    {
        $submittedIds = collect($submission->payload['dependents'] ?? [])
            ->pluck('existing_id')
            ->filter()
            ->all();

        return $submission->employee->dependents()
            ->whereNotIn('id', $submittedIds)
            ->get();
    }

    public function getUnaddressedDiplomas(CensusSubmission $submission): \Illuminate\Support\Collection
    {
        $submittedIds = collect($submission->payload['diplomas'] ?? [])
            ->pluck('existing_id')
            ->filter()
            ->all();

        return $submission->employee->diplomas()
            ->whereNotIn('id', $submittedIds)
            ->get();
    }
}
