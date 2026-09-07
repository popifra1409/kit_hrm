<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;

class LeaveEntitlementService
{
    public const PERMISSION_THRESHOLD_DAYS = 10;

    public function getAnnualEntitlement(Employee $employee, int $year): int
    {
        return match ($employee->administrative_status) {
            'fonctionnaire_affecte', 'fonctionnaire_detache' => 30,
            'contractuel_fp', 'contractuel_structure' => $this->contractuelEntitlement($employee, $year),
            'stagiaire' => 0,
            default => 0,
        };
    }

    protected function contractuelEntitlement(Employee $employee, int $year): int
    {
        $base = 18;

        if (!$employee->recruitment_date) {
            return $base;
        }

        $referenceDate = \Carbon\Carbon::createFromDate($year, 12, 31);
        $seniorityYears = $employee->recruitment_date->diffInYears($referenceDate);

        return $seniorityYears >= 5 ? $base + 2 : $base;
    }

    /**
     * L'employé est-il éligible au Congé Rayon X (basé sur son service actuel) ?
     */
    public function isEligibleForRayonX(Employee $employee): bool
    {
        $serviceName = $employee->currentService?->name;

        if (!$serviceName) {
            return false;
        }

        return str_contains(mb_strtolower($serviceName), 'radiolog');
    }

    /**
     * Jours déjà utilisés (approuvés ou en attente) pour un type de congé donné, sur une année.
     */
    public function getUsedDays(Employee $employee, LeaveType $leaveType, int $year): int
    {
        return (int) Leave::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', $year)
            ->sum('total_days');
    }

    /**
     * Solde disponible = droit - déjà pris/en attente. Purement informatif (calculé à la volée).
     */
    public function getAvailableDays(Employee $employee, LeaveType $leaveType, int $year): ?int
    {
        $entitlement = match ($leaveType->code) {
            'CA' => $this->getAnnualEntitlement($employee, $year),
            'CRX' => $this->isEligibleForRayonX($employee) ? 30 : 0,
            'CMAT' => null, // pas un solde annuel, événementiel
            default => null,
        };

        if ($entitlement === null) {
            return null;
        }

        return max(0, $entitlement - $this->getUsedDays($employee, $leaveType, $year));
    }

    /**
     * Total des jours de Permission d'Absence déjà pris/en attente sur l'année.
     */
    public function getPermissionDaysUsedThisYear(Employee $employee, int $year, ?int $excludeLeaveId = null): int
    {
        $query = Leave::where('employee_id', $employee->id)
            ->whereHas('leaveType', fn($q) => $q->where('code', 'PERM'))
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', $year);

        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        return (int) $query->sum('total_days');
    }

    /**
     * Sur les `newDays` jours de la nouvelle permission demandée, combien dépassent
     * le seuil cumulé de 10 jours/an et doivent donc être déduits du congé annuel ?
     */
    public function getDaysExceedingPermissionThreshold(Employee $employee, int $year, int $newDays, ?int $excludeLeaveId = null): int
    {
        $alreadyUsed = $this->getPermissionDaysUsedThisYear($employee, $year, $excludeLeaveId);
        $totalAfter = $alreadyUsed + $newDays;

        if ($totalAfter <= self::PERMISSION_THRESHOLD_DAYS) {
            return 0;
        }

        $excess = $totalAfter - self::PERMISSION_THRESHOLD_DAYS;

        return min($excess, $newDays);
    }
}
