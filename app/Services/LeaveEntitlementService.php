<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\SystemSetting;
use Carbon\Carbon;

class LeaveEntitlementService
{
    // ========================================
    // PARAMÈTRES (lus depuis SystemSetting, avec valeurs par défaut de repli)
    // ========================================

    /**
     * Catégorie générique du statut administratif, pour regrouper les réglages
     * (fonctionnaire_affecte/detache -> 'fonctionnaire', contractuel_fp/structure -> 'contractuel').
     */
    protected function statusCategory(string $administrativeStatus): string
    {
        return match ($administrativeStatus) {
            'fonctionnaire_affecte', 'fonctionnaire_detache' => 'fonctionnaire',
            'contractuel_fp', 'contractuel_structure' => 'contractuel',
            'stagiaire' => 'stagiaire',
            default => 'contractuel',
        };
    }

    /**
     * Durée du cycle de service EN MOIS, paramétrable par statut
     * (un fonctionnaire et un contractuel de la structure peuvent avoir des
     * cycles différents selon les textes en vigueur).
     */
    protected function cycleMonths(string $administrativeStatus): int
    {
        $category = $this->statusCategory($administrativeStatus);

        return (int) SystemSetting::get("leave.cycle_months_{$category}", 12);
    }

    protected function minCyclesBeforeFirstLeave(): int
    {
        return (int) SystemSetting::get('leave.min_cycles_before_first_leave', 1);
    }

    protected function baseDays(string $administrativeStatus): int
    {
        return match ($administrativeStatus) {
            'fonctionnaire_affecte', 'fonctionnaire_detache' => (int) SystemSetting::get('leave.base_days_fonctionnaire', 30),
            'contractuel_fp', 'contractuel_structure' => (int) SystemSetting::get('leave.base_days_contractuel', 18),
            'stagiaire' => (int) SystemSetting::get('leave.base_days_stagiaire', 0),
            default => 0,
        };
    }

    protected function bonusEveryNCycles(): int
    {
        return (int) SystemSetting::get('leave.bonus_every_n_cycles', 5);
    }

    protected function bonusDays(): int
    {
        return (int) SystemSetting::get('leave.bonus_days', 2);
    }

    protected function bonusIsCumulative(): bool
    {
        return (bool) SystemSetting::get('leave.bonus_cumulative', true);
    }

    protected function rayonXDays(): int
    {
        return (int) SystemSetting::get('leave.rayon_x_days', 30);
    }

    public function permissionThresholdDays(): int
    {
        return (int) SystemSetting::get('leave.permission_threshold_days', 10);
    }

    protected function permissionPeriodBasis(): string
    {
        return SystemSetting::get('leave.permission_period_basis', 'calendar_year');
    }

    // ========================================
    // CYCLES DE SERVICE (N mois glissants depuis le recrutement)
    // ========================================

    public function getServiceYear(Employee $employee, ?Carbon $asOf = null): int
    {
        if (!$employee->recruitment_date) {
            return 0;
        }

        $asOf = $asOf ?? now();
        $monthsCompleted = $employee->recruitment_date->diffInMonths($asOf);

        return (int) intdiv($monthsCompleted, $this->cycleMonths($employee->administrative_status));
    }

    public function getServiceYearBounds(Employee $employee, int $serviceYear): array
    {
        $cycleMonths = $this->cycleMonths($employee->administrative_status);
        $start = $employee->recruitment_date->copy()->addMonths(($serviceYear - 1) * $cycleMonths);
        $end = $start->copy()->addMonths($cycleMonths)->subDay();

        return [$start, $end];
    }

    public function resolveServiceYearForDate(Employee $employee, Carbon $date): int
    {
        if (!$employee->recruitment_date) {
            return 0;
        }

        $monthsCompleted = $employee->recruitment_date->diffInMonths($date);

        return (int) intdiv($monthsCompleted, $this->cycleMonths($employee->administrative_status)) + 1;
    }

    public function isEligibleForLeave(Employee $employee, ?Carbon $asOf = null): bool
    {
        return $this->getServiceYear($employee, $asOf) >= $this->minCyclesBeforeFirstLeave();
    }

    public function getNextEligibilityDate(Employee $employee, ?Carbon $asOf = null): ?Carbon
    {
        if (!$employee->recruitment_date) {
            return null;
        }

        $asOf = $asOf ?? now();
        $currentServiceYear = $this->getServiceYear($employee, $asOf);
        $targetCycle = max($currentServiceYear + 1, $this->minCyclesBeforeFirstLeave());

        return $employee->recruitment_date->copy()->addMonths($targetCycle * $this->cycleMonths($employee->administrative_status));
    }

    // ========================================
    // DROITS (ENTITLEMENT)
    // ========================================

    public function getAnnualEntitlement(Employee $employee, int $serviceYear): int
    {
        $base = $this->baseDays($employee->administrative_status);

        if ($base === 0) {
            return 0;
        }

        $bonusEveryN = $this->bonusEveryNCycles();
        $bonusDays = $this->bonusDays();

        if ($bonusEveryN <= 0 || $bonusDays <= 0) {
            return $base;
        }

        $milestones = intdiv(max($serviceYear, 0), $bonusEveryN);

        $bonus = $this->bonusIsCumulative()
            ? $milestones * $bonusDays
            : ($milestones > 0 ? $bonusDays : 0);

        return $base + $bonus;
    }

    public function isEligibleForRayonX(Employee $employee): bool
    {
        return (bool) $employee->currentService?->is_rayon_x_eligible;
    }

    // ========================================
    // SOLDE (informatif, calculé à la volée)
    // ========================================

    public function getUsedDays(Employee $employee, LeaveType $leaveType, int $serviceYear, ?int $excludeLeaveId = null): int
    {
        $query = Leave::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('service_year', $serviceYear)
            ->whereIn('status', ['approved', 'pending']);

        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        return (int) $query->sum('total_days');
    }

    public function getAvailableDays(Employee $employee, LeaveType $leaveType, ?int $serviceYear = null): ?array
    {
        $serviceYear = $serviceYear ?? $this->getServiceYear($employee);

        if (!$this->isEligibleForLeave($employee)) {
            return [
                'eligible' => false,
                'entitlement' => 0,
                'used' => 0,
                'available' => 0,
                'next_eligibility_date' => $this->getNextEligibilityDate($employee)?->format('Y-m-d'),
            ];
        }

        $entitlement = match ($leaveType->code) {
            'CA' => $this->getAnnualEntitlement($employee, $serviceYear),
            'CRX' => $this->isEligibleForRayonX($employee) ? $this->rayonXDays() : 0,
            default => null,
        };

        if ($entitlement === null) {
            return null;
        }

        $used = $this->getUsedDays($employee, $leaveType, $serviceYear);

        return [
            'eligible' => true,
            'service_year' => $serviceYear,
            'entitlement' => $entitlement,
            'used' => $used,
            'available' => max(0, $entitlement - $used),
        ];
    }

    // ========================================
    // PERMISSION D'ABSENCE (seuil cumulé configurable)
    // ========================================

    public function getPermissionDaysUsed(Employee $employee, int $referencePeriod, ?int $excludeLeaveId = null): int
    {
        $query = Leave::where('employee_id', $employee->id)
            ->whereHas('leaveType', fn($q) => $q->where('code', 'PERM'))
            ->whereIn('status', ['approved', 'pending']);

        if ($this->permissionPeriodBasis() === 'service_cycle') {
            $query->where('service_year', $referencePeriod);
        } else {
            $query->whereYear('start_date', $referencePeriod);
        }

        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        return (int) $query->sum('total_days');
    }

    /**
     * Détermine la période de référence (année civile ou cycle de service) à utiliser
     * pour une date de permission donnée, selon le mode configuré.
     */
    public function resolvePermissionPeriod(Employee $employee, Carbon $date): int
    {
        return $this->permissionPeriodBasis() === 'service_cycle'
            ? $this->resolveServiceYearForDate($employee, $date)
            : (int) $date->format('Y');
    }

    public function getDaysExceedingPermissionThreshold(Employee $employee, int $referencePeriod, int $newDays, ?int $excludeLeaveId = null): int
    {
        $alreadyUsed = $this->getPermissionDaysUsed($employee, $referencePeriod, $excludeLeaveId);
        $threshold = $this->permissionThresholdDays();
        $totalAfter = $alreadyUsed + $newDays;

        if ($totalAfter <= $threshold) {
            return 0;
        }

        return min($totalAfter - $threshold, $newDays);
    }

    // ========================================
    // RAPPORTS
    // ========================================

    public function getLastAnnualLeaveDate(Employee $employee): ?Carbon
    {
        $last = Leave::where('employee_id', $employee->id)
            ->whereHas('leaveType', fn($q) => $q->where('code', 'CA'))
            ->where('status', 'approved')
            ->orderByDesc('end_date')
            ->first();

        return $last?->end_date;
    }
}
