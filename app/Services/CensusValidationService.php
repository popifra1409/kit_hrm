<?php

namespace App\Services;

use App\Models\CensusSubmission;
use App\Models\Dependent;
use App\Models\EmployeeDiploma;
use Illuminate\Support\Facades\DB;

class CensusValidationService
{
    public function apply(CensusSubmission $submission, int $validatorId): void
    {
        DB::transaction(function () use ($submission, $validatorId) {
            $employee = $submission->employee;
            $payload = $submission->payload;

            $personal = $payload['personal'] ?? [];
            $employee->fill([
                'phone' => $personal['phone'] ?? $employee->phone,
                'email' => $personal['email'] ?? $employee->email,
                'address' => $personal['address'] ?? $employee->address,
                'city' => $personal['city'] ?? $employee->city,
            ]);
            $employee->save();

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
                'status' => 'validated',
                'validated_by' => $validatorId,
                'validated_at' => now(),
            ]);
        });
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
