<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Leave;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Department;
use App\Models\Service;
use App\Models\LeaveApprovalStep;
use App\Services\LeaveEntitlementService;

class LeavesDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Tableau de Bord Congés';
    protected static ?string $title = 'Tableau de Bord - Gestion des Congés';
    protected static ?string $navigationGroup = '🏖️ Congés & Absences';

    protected static string $view = 'filament.pages.leaves-dashboard';

    // ========================================
    // FILTRES (nouveaux tableaux par structure)
    // ========================================

    public ?int $departmentId = null;
    public ?int $serviceId = null;
    public int $yearsThreshold = 2;
    public int $lowBalanceThreshold = 10;

    public function mount(): void
    {
        $this->form->fill([
            'departmentId' => null,
            'serviceId' => null,
            'yearsThreshold' => 2,
            'lowBalanceThreshold' => 10,
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('departmentId')
                            ->label('Département Médical')
                            ->options(fn() => Department::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Tous')
                            ->live(),

                        Forms\Components\Select::make('serviceId')
                            ->label('Service')
                            ->options(fn() => Service::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Tous')
                            ->live(),

                        Forms\Components\TextInput::make('yearsThreshold')
                            ->label('Seuil "sans congé depuis" (années)')
                            ->numeric()->minValue(1)->live()
                            ->afterStateUpdated(fn($state) => $this->yearsThreshold = (int) $state),

                        Forms\Components\TextInput::make('lowBalanceThreshold')
                            ->label('Seuil "solde faible" (jours)')
                            ->numeric()->minValue(0)->live()
                            ->afterStateUpdated(fn($state) => $this->lowBalanceThreshold = (int) $state),
                    ]),
            ]);
    }

    protected function baseEmployeeQuery()
    {
        $query = Employee::where('is_active', true)->whereNotNull('recruitment_date');

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        if ($this->serviceId) {
            $query->where('current_service_id', $this->serviceId);
        }

        return $query;
    }

    // ========================================
    // STATS EXISTANTES (conservées)
    // ========================================

    public function getStats()
    {
        $currentYear = now()->year;

        return [
            'pending' => Leave::where('status', 'pending')->count(),

            'approved_this_month' => Leave::where('status', 'approved')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            'on_leave' => Leave::where('status', 'approved')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),

            'total_days_year' => Leave::where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->sum('total_days'),
        ];
    }

    public function getLeavesByType()
    {
        $currentYear = now()->year;

        return LeaveType::withCount([
            'leaves as approved_count' => function ($query) use ($currentYear) {
                $query->where('status', 'approved')->whereYear('start_date', $currentYear);
            },
            'leaves as pending_count' => function ($query) use ($currentYear) {
                $query->where('status', 'pending')->whereYear('start_date', $currentYear);
            }
        ])
            ->withSum([
                'leaves as total_days' => function ($query) use ($currentYear) {
                    $query->where('status', 'approved')->whereYear('start_date', $currentYear);
                }
            ], 'total_days')
            ->where('is_active', true)
            ->get();
    }

    public function getTopEmployeesByLeave()
    {
        $currentYear = now()->year;

        return Employee::withSum([
            'leaves as total_leave_days' => function ($query) use ($currentYear) {
                $query->where('status', 'approved')->whereYear('start_date', $currentYear);
            }
        ], 'total_days')
            ->where('is_active', true)
            ->orderByDesc('total_leave_days')
            ->limit(10)
            ->get();
    }

    public function getMonthlyTrend()
    {
        $currentYear = now()->year;
        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            $count = Leave::where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->whereMonth('start_date', $i)
                ->count();

            $months[] = [
                'month_fr' => $this->getMonthNameFr($i),
                'count' => $count,
            ];
        }

        return $months;
    }

    protected function getMonthNameFr($month)
    {
        $months = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];

        return $months[$month] ?? '';
    }

    // ✅ CORRIGÉ : n'utilise plus l'ancienne table LeaveBalance (abandonnée),
    // calcule le solde à la volée via LeaveEntitlementService.
    public function getEmployeesWithLowBalance()
    {
        $entitlementService = app(LeaveEntitlementService::class);
        $caType = LeaveType::where('code', 'CA')->first();

        if (!$caType) {
            return collect();
        }

        $results = collect();

        Employee::where('is_active', true)
            ->whereNotNull('recruitment_date')
            ->each(function ($employee) use ($entitlementService, $caType, &$results) {
                if (!$entitlementService->isEligibleForLeave($employee)) {
                    return;
                }

                $balance = $entitlementService->getAvailableDays($employee, $caType);

                if ($balance && $balance['available'] < $this->lowBalanceThreshold) {
                    $results->push([
                        'employee' => $employee,
                        'available' => $balance['available'],
                        'entitlement' => $balance['entitlement'],
                    ]);
                }
            });

        return $results->sortBy('available')->take(10)->values();
    }

    // ✅ CORRIGÉ : reflète le circuit à 6 étapes au lieu des anciens statuts approved_n1/n2
    public function getPendingApprovalsByStep()
    {
        return LeaveApprovalStep::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(function ($step) {
                return [
                    'step' => $step,
                    'count' => Leave::where('status', 'pending')
                        ->where('current_approval_step_id', $step->id)
                        ->count(),
                ];
            });
    }

    // ========================================
    // NOUVEAUX TABLEAUX (structure, retours, éligibilité)
    // ========================================

    public function getViewData(): array
    {
        $entitlementService = app(LeaveEntitlementService::class);

        $currentlyOnLeave = Leave::with(['employee.currentService', 'employee.department', 'leaveType'])
            ->where('status', 'approved')
            ->where('has_returned', false)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->when($this->departmentId, fn($q) => $q->whereHas('employee', fn($eq) => $eq->where('department_id', $this->departmentId)))
            ->when($this->serviceId, fn($q) => $q->whereHas('employee', fn($eq) => $eq->where('current_service_id', $this->serviceId)))
            ->orderBy('end_date')
            ->get();

        $overdueReturn = Leave::with(['employee.currentService', 'employee.department', 'leaveType'])
            ->where('status', 'approved')
            ->where('has_returned', false)
            ->whereDate('end_date', '<', now())
            ->when($this->departmentId, fn($q) => $q->whereHas('employee', fn($eq) => $eq->where('department_id', $this->departmentId)))
            ->when($this->serviceId, fn($q) => $q->whereHas('employee', fn($eq) => $eq->where('current_service_id', $this->serviceId)))
            ->orderBy('end_date')
            ->get();

        $caType = LeaveType::where('code', 'CA')->first();
        $eligible = collect();

        if ($caType) {
            foreach ($this->baseEmployeeQuery()->with(['currentService', 'department'])->get() as $employee) {
                if (!$entitlementService->isEligibleForLeave($employee)) {
                    continue;
                }

                $balance = $entitlementService->getAvailableDays($employee, $caType);

                if ($balance && $balance['available'] > 0) {
                    $eligible->push([
                        'employee' => $employee,
                        'available' => $balance['available'],
                        'entitlement' => $balance['entitlement'],
                    ]);
                }
            }

            $eligible = $eligible->sortByDesc('available')->values();
        }

        $longAgo = collect();

        foreach ($this->baseEmployeeQuery()->with(['currentService', 'department'])->get() as $employee) {
            if (!$entitlementService->isEligibleForLeave($employee)) {
                continue;
            }

            $lastLeave = $entitlementService->getLastAnnualLeaveDate($employee);

            if (!$lastLeave) {
                $longAgo->push(['employee' => $employee, 'last_leave' => null, 'years_ago' => null]);
                continue;
            }

            $yearsAgo = $lastLeave->diffInYears(now());

            if ($yearsAgo >= $this->yearsThreshold) {
                $longAgo->push(['employee' => $employee, 'last_leave' => $lastLeave, 'years_ago' => $yearsAgo]);
            }
        }

        $longAgo = $longAgo->sortByDesc('years_ago')->values();

        return [
            'stats' => $this->getStats(),
            'leavesByType' => $this->getLeavesByType(),
            'topEmployees' => $this->getTopEmployeesByLeave(),
            'monthlyTrend' => $this->getMonthlyTrend(),
            'lowBalance' => $this->getEmployeesWithLowBalance(),
            'pendingByStep' => $this->getPendingApprovalsByStep(),
            'currentlyOnLeave' => $currentlyOnLeave,
            'overdueReturn' => $overdueReturn,
            'eligible' => $eligible,
            'longAgo' => $longAgo,
            'yearsThreshold' => $this->yearsThreshold,
        ];
    }
}
