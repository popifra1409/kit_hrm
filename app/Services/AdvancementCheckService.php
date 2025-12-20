<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryGrid;
use Carbon\Carbon;

class AdvancementCheckService
{
    /**
     * Vérifier tous les employés éligibles à l'avancement
     */
    public function checkAllEligibleEmployees()
    {
        $eligible = [];

        $employees = Employee::where('is_active', true)
            ->whereNotNull('current_echelon')
            ->whereNotNull('echelon_start_date')
            ->get();

        foreach ($employees as $employee) {
            if ($this->isEligibleForAdvancement($employee)) {
                $eligible[] = [
                    'employee' => $employee,
                    'current_echelon' => $employee->current_echelon,
                    'next_echelon' => $this->getNextEchelon($employee),
                    'months_in_current' => $this->getMonthsInCurrentEchelon($employee),
                    'required_months' => $this->getRequiredMonths($employee),
                    'eligible_date' => $this->getEligibleDate($employee),
                ];
            }
        }

        return collect($eligible);
    }

    /**
     * Vérifier si un employé est éligible à l'avancement
     */
    public function isEligibleForAdvancement(Employee $employee)
    {
        if (!$employee->current_echelon || !$employee->echelon_start_date) {
            return false;
        }

        // Vérifier si déjà au dernier échelon de la catégorie
        if ($this->isAtMaxEchelon($employee)) {
            return false;
        }

        $monthsInCurrent = $this->getMonthsInCurrentEchelon($employee);
        $requiredMonths = $this->getRequiredMonths($employee);

        return $monthsInCurrent >= $requiredMonths;
    }

    /**
     * Obtenir le nombre de mois dans l'échelon actuel
     */
    public function getMonthsInCurrentEchelon(Employee $employee)
    {
        if (!$employee->echelon_start_date) {
            return 0;
        }

        return Carbon::parse($employee->echelon_start_date)->diffInMonths(now());
    }

    /**
     * Obtenir le nombre de mois requis pour l'avancement
     */
    public function getRequiredMonths(Employee $employee)
    {
        // Selon la grille CHUY:
        // - Échelons 1-5 : 24 mois (2 ans)
        // - Échelons 6-10 : 30 mois (2.5 ans)
        // - Échelons 11+ : 36 mois (3 ans)

        $currentEchelon = (int) $employee->current_echelon;

        if ($currentEchelon <= 5) {
            return 24;
        } elseif ($currentEchelon <= 10) {
            return 30;
        } else {
            return 36;
        }
    }

    /**
     * Obtenir le prochain échelon
     */
    public function getNextEchelon(Employee $employee)
    {
        $current = (int) $employee->current_echelon;
        return min($current + 1, 12); // Max échelon = 12
    }

    /**
     * Vérifier si l'employé est au dernier échelon
     */
    public function isAtMaxEchelon(Employee $employee)
    {
        return (int) $employee->current_echelon >= 12;
    }

    /**
     * Obtenir la date d'éligibilité
     */
    public function getEligibleDate(Employee $employee)
    {
        if (!$employee->echelon_start_date) {
            return null;
        }

        $requiredMonths = $this->getRequiredMonths($employee);
        return Carbon::parse($employee->echelon_start_date)->addMonths($requiredMonths);
    }

    /**
     * Obtenir le nouveau salaire après avancement
     */
    public function getNewSalary(Employee $employee)
    {
        $nextEchelon = $this->getNextEchelon($employee);

        $salaryGrid = SalaryGrid::where('category', $employee->category)
            ->where('echelon', $nextEchelon)
            ->first();

        return $salaryGrid?->base_salary;
    }
}
