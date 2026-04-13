<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Position;
use App\Models\ContractType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Carbon\Carbon;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        // Ignorer la ligne d'en-tête si présente
        if ($row['matricule'] == 'Matricule' || empty($row['matricule'])) {
            return null;
        }

        // Extraire nom et prénom
        $fullName = $row['nom'] ?? '';
        $firstName = $row['prenom'] ?? '';

        // Si le nom contient "EPSE", gérer différemment
        $lastName = $fullName;

        // Extraire catégorie et échelon actuels
        $categoryEchelon = $row['categorie_echelon_actuelle'] ?? '';
        $categoryNumber = null;
        $echelonNumber = null;

        if (preg_match('/(\d+)\/(\d+)/', $categoryEchelon, $matches)) {
            $categoryNumber = (int)$matches[1];
            $echelonNumber = (int)$matches[2];
        }

        // Trouver ou créer le poste
        $qualification = $row['qualification'] ?? 'Non spécifié';
        $position = Position::firstOrCreate(
            ['name' => $qualification],
            [
                'code' => strtoupper(substr($qualification, 0, 10)),
                'description' => $qualification,
            ]
        );

        // Déterminer le type de contrat (CDI par défaut)
        $contractType = ContractType::where('code', 'CDI')->first();

        // Déterminer le type de personnel (soignant/non-soignant)
        $soignantKeywords = ['INFIRMIER', 'MEDECIN', 'SAGE', 'AIDE-SOIGNANT', 'LABORAT', 'ANESTHE', 'KINE', 'RADIOL', 'PHARMAC', 'CHIRURG'];
        $personnelType = 'non_soignant';

        foreach ($soignantKeywords as $keyword) {
            if (stripos($qualification, $keyword) !== false) {
                $personnelType = 'soignant';
                break;
            }
        }

        // Convertir les dates
        $birthDate = $this->parseDate($row['date_de_naissance'] ?? null);
        $recruitmentDate = $this->parseDate($row['date_recrutement'] ?? null);
        $serviceStartDate = $this->parseDate($row['date_de_prise_de_service'] ?? null);
        $retirementDate = $this->parseDate($row['date_de_retraite'] ?? null);

        return new Employee([
            'matricule' => $row['matricule'],
            'first_name' => $firstName ?: 'N/A',
            'last_name' => $lastName,
            'category_recruitment' => $row['categorie_echelon_recrutement'] ?? null,
            'category_current' => $categoryEchelon,
            'category_number' => $categoryNumber,
            'echelon_number' => $echelonNumber,
            'qualification' => $qualification,
            'position_id' => $position->id,
            'contract_type_id' => $contractType ? $contractType->id : null,
            'personnel_type' => $personnelType,
            'birth_date' => $birthDate,
            'recruitment_date' => $recruitmentDate,
            'service_start_date' => $serviceStartDate,
            'retirement_date' => $retirementDate,
            'retirement_age' => 60,
            'contract_number' => $row['n_de_contrat_decision'] ?? null,
            'bank_account_number' => $row['n_de_compte_bancaire'] ?? null,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Si c'est un timestamp Excel
            if (is_numeric($date)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }

            // Sinon essayer de parser normalement
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'matricule' => 'required',
        ];
    }

    public function headingRow(): int
    {
        return 4; // Les en-têtes sont à la ligne 4 dans votre fichier
    }
}
