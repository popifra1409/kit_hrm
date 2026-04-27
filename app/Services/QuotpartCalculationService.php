<?php

namespace App\Services;

use App\Models\QuotpartPeriod;
use App\Models\QuotpartDistribution;
use App\Models\QuotpartParameter;
use App\Models\QuotpartDeductionType;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\MedicalActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuotpartCalculationService
{
    /**
     * Calculer les quote-parts pour une période
     */
    public function calculateForPeriod(QuotpartPeriod $period)
    {
        DB::beginTransaction();

        try {
            // 1. Vérifier que la période est validée
            if ($period->status !== 'validated') {
                throw new \Exception("La période doit être validée avant le calcul.");
            }

            // 2. Récupérer les paramètres de calcul
            $parameters = $this->getParameters();

            // 3. Récupérer tous les employés actifs
            $employees = Employee::where('status', 'active')->get();

            if ($employees->isEmpty()) {
                throw new \Exception("Aucun employé actif trouvé.");
            }

            // 4. Calculer les points pour chaque employé
            $employeePoints = [];
            $totalPoints = 0;

            foreach ($employees as $employee) {
                $points = $this->calculateEmployeePoints($employee, $period, $parameters);
                $employeePoints[$employee->id] = $points;
                $totalPoints += $points['total_points'];
            }

            if ($totalPoints == 0) {
                throw new \Exception("Le total des points est zéro. Impossible de distribuer.");
            }

            // 5. Distribuer proportionnellement le montant
            foreach ($employees as $employee) {
                $points = $employeePoints[$employee->id];

                // Calcul de la quote-part brute
                $grossQuotpart = ($points['total_points'] / $totalPoints) * $period->quotpart_amount;

                // Calcul des retenues
                $deductions = $this->calculateDeductions($grossQuotpart);

                // Calcul du net
                $netQuotpart = $grossQuotpart - $deductions['total'];

                // Enregistrement
                QuotpartDistribution::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period_id' => $period->id,
                    ],
                    [
                        'base_indice_points' => $points['base_indice'],
                        'evaluation_points' => $points['evaluation'],
                        'medical_activity_points' => $points['medical_activity'],
                        'management_bonus_points' => $points['management_bonus'],
                        'anciennete_points' => $points['anciennete'],
                        'total_points' => $points['total_points'],
                        'gross_quotpart' => $grossQuotpart,
                        'cnps_deduction' => $deductions['cnps'],
                        'irpp_deduction' => $deductions['irpp'],
                        'other_deductions' => $deductions['other'],
                        'total_deductions' => $deductions['total'],
                        'net_quotpart' => $netQuotpart,
                        'calculation_details' => json_encode([
                            'points_detail' => $points,
                            'deductions_detail' => $deductions,
                            'total_employees' => $employees->count(),
                            'total_points_all' => $totalPoints,
                            'calculated_at' => now()->toDateTimeString(),
                        ]),
                        'status' => 'calculated',
                        'calculated_at' => now(),
                    ]
                );
            }

            // 6. Mettre à jour le statut de la période
            $period->update([
                'status' => 'calculated',
                'calculated_at' => now(),
            ]);

            DB::commit();

            Log::info("Quote-parts calculées pour la période {$period->code}", [
                'employees_count' => $employees->count(),
                'total_points' => $totalPoints,
                'quotpart_amount' => $period->quotpart_amount,
            ]);

            return [
                'success' => true,
                'employees_count' => $employees->count(),
                'total_points' => $totalPoints,
                'quotpart_amount' => $period->quotpart_amount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur calcul quote-parts: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculer les points d'un employé
     */
    protected function calculateEmployeePoints(Employee $employee, QuotpartPeriod $period, array $parameters)
    {
        $points = [
            'base_indice' => 0,
            'evaluation' => 0,
            'medical_activity' => 0,
            'management_bonus' => 0,
            'anciennete' => 0,
            'total_points' => 0,
        ];

        // 1. POINTS BASE (Indice)
        if ($employee->indice && isset($parameters['indice_weight'])) {
            $points['base_indice'] = $employee->indice * $parameters['indice_weight'];
        }

        // 2. POINTS ÉVALUATION
        $evaluations = EmployeeEvaluation::where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->with('criterion')
            ->get();

        foreach ($evaluations as $evaluation) {
            if ($evaluation->criterion) {
                // Score pondéré = score × poids du critère
                $weightedScore = $evaluation->score * $evaluation->criterion->weight;

                // Application du poids général d'évaluation
                if (isset($parameters['evaluation_weight'])) {
                    $weightedScore *= $parameters['evaluation_weight'];
                }

                $points['evaluation'] += $weightedScore;
            }
        }

        // 3. POINTS ACTIVITÉS MÉDICALES (Personnel soignant uniquement)
        if ($employee->personnel_type === 'soignant') {
            $activities = MedicalActivity::where('employee_id', $employee->id)
                ->where('period_id', $period->id)
                ->where('is_validated', true)
                ->get();

            foreach ($activities as $activity) {
                $activityPoints = 0;

                switch ($activity->activity_type) {
                    case 'consultation':
                        $activityPoints = $activity->quantity * ($parameters['consultation_weight'] ?? 0.5);
                        break;
                    case 'prescription':
                        $activityPoints = $activity->quantity * ($parameters['prescription_weight'] ?? 0.3);
                        break;
                    case 'acte':
                        $activityPoints = $activity->quantity * ($parameters['acte_weight'] ?? 1.0);
                        break;
                    case 'garde':
                        $activityPoints = $activity->quantity * ($parameters['garde_weight'] ?? 5.0);
                        break;
                    case 'astreinte':
                        $activityPoints = $activity->quantity * ($parameters['astreinte_weight'] ?? 3.0);
                        break;
                }

                $points['medical_activity'] += $activityPoints;
            }
        }

        // 4. BONUS MANAGEMENT (Selon le poste)
        $managementBonus = $this->getManagementBonus($employee, $parameters);
        $points['management_bonus'] = $managementBonus;

        // 5. POINTS ANCIENNETÉ
        if ($employee->hire_date && isset($parameters['anciennete_weight'])) {
            $yearsOfService = $employee->hire_date->diffInYears(now());
            $points['anciennete'] = $yearsOfService * $parameters['anciennete_weight'];
        }

        // TOTAL
        $points['total_points'] =
            $points['base_indice'] +
            $points['evaluation'] +
            $points['medical_activity'] +
            $points['management_bonus'] +
            $points['anciennete'];

        return $points;
    }

    /**
     * Récupérer les paramètres de calcul
     */
    protected function getParameters()
    {
        $params = QuotpartParameter::where('is_active', true)->get();

        $parameters = [];
        foreach ($params as $param) {
            $parameters[$param->code] = $param->weight;
        }

        return $parameters;
    }

    /**
     * Calculer le bonus management
     */
    protected function getManagementBonus(Employee $employee, array $parameters)
    {
        // Vérifier si l'employé a un poste de direction
        if (!$employee->position) {
            return 0;
        }

        $positionName = strtolower($employee->position->name ?? '');

        // Directeur
        if (str_contains($positionName, 'directeur') && !str_contains($positionName, 'sous')) {
            return $parameters['directeur_bonus'] ?? 20.0;
        }

        // Sous-Directeur
        if (str_contains($positionName, 'sous-directeur')) {
            return $parameters['sous_directeur_bonus'] ?? 15.0;
        }

        // Chef de Département
        if (str_contains($positionName, 'chef') && str_contains($positionName, 'département')) {
            return $parameters['chef_departement_bonus'] ?? 12.0;
        }

        // Chef de Service
        if (str_contains($positionName, 'chef') && str_contains($positionName, 'service')) {
            return $parameters['chef_service_bonus'] ?? 10.0;
        }

        return 0;
    }

    /**
     * Calculer les retenues
     */
    protected function calculateDeductions($grossAmount)
    {
        $deductions = [
            'cnps' => 0,
            'irpp' => 0,
            'other' => 0,
            'total' => 0,
        ];

        $deductionTypes = QuotpartDeductionType::where('is_active', true)
            ->orderBy('order')
            ->get();

        foreach ($deductionTypes as $type) {
            $amount = 0;

            switch ($type->calculation_type) {
                case 'percentage':
                    $amount = ($grossAmount * $type->rate) / 100;
                    break;

                case 'fixed':
                    $amount = $type->fixed_amount;
                    break;

                case 'progressive':
                    $amount = $this->calculateProgressiveDeduction($grossAmount, $type->progressive_brackets);
                    break;
            }

            // Catégoriser la retenue
            $code = strtolower($type->code);
            if (str_contains($code, 'cnps')) {
                $deductions['cnps'] += $amount;
            } elseif (str_contains($code, 'irpp') || str_contains($code, 'impot')) {
                $deductions['irpp'] += $amount;
            } else {
                $deductions['other'] += $amount;
            }

            $deductions['total'] += $amount;
        }

        return $deductions;
    }

    /**
     * Calculer une retenue progressive (barème IRPP)
     */
    protected function calculateProgressiveDeduction($amount, $brackets)
    {
        if (empty($brackets)) {
            return 0;
        }

        // Si c'est une chaîne JSON, la décoder
        if (is_string($brackets)) {
            $brackets = json_decode($brackets, true);
        }

        $totalTax = 0;
        $remainingAmount = $amount;

        foreach ($brackets as $bracket) {
            $min = $bracket['min'] ?? 0;
            $max = $bracket['max'] ?? null;
            $rate = $bracket['rate'] ?? 0;

            if ($remainingAmount <= 0) {
                break;
            }

            // Montant dans cette tranche
            if ($max === null) {
                // Dernière tranche (illimitée)
                $taxableInBracket = $remainingAmount;
            } else {
                $bracketSize = $max - $min;
                $taxableInBracket = min($remainingAmount, $bracketSize);
            }

            // Calculer l'impôt pour cette tranche
            $totalTax += ($taxableInBracket * $rate) / 100;
            $remainingAmount -= $taxableInBracket;
        }

        return $totalTax;
    }
}
