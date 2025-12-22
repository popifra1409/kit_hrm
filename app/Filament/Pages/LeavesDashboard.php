<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\Employee;
use App\Models\LeaveType;

class LeavesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Tableau de Bord Congés';
    protected static ?string $title = 'Tableau de Bord - Gestion des Congés';
    protected static ?string $navigationGroup = '🏖️ Congés & Absences';
    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.leaves-dashboard';

    public function getStats()
    {
        $currentYear = now()->year;

        return [
            // Demandes en attente
            'pending' => Leave::where('status', 'pending')->count(),

            // Demandes approuvées ce mois
            'approved_this_month' => Leave::where('status', 'approved')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            // Employés actuellement en congé
            'on_leave' => Leave::where('status', 'approved')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),

            // Total jours de congés approuvés cette année
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
                $query->where('status', 'approved')
                    ->whereYear('start_date', $currentYear);
            },
            'leaves as pending_count' => function ($query) use ($currentYear) {
                $query->where('status', 'pending')
                    ->whereYear('start_date', $currentYear);
            }
        ])
            ->withSum([
                'leaves as total_days' => function ($query) use ($currentYear) {
                    $query->where('status', 'approved')
                        ->whereYear('start_date', $currentYear);
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
                $query->where('status', 'approved')
                    ->whereYear('start_date', $currentYear);
            }
        ], 'total_days')
            ->where('is_active', true)
            ->orderByDesc('total_leave_days')
            ->limit(10)
            ->get();
    }

    public function getEmployeesWithLowBalance()
    {
        $currentYear = now()->year;
        $caType = LeaveType::where('code', 'CA')->first();

        if (!$caType) return collect();

        return LeaveBalance::where('year', $currentYear)
            ->where('leave_type_id', $caType->id)
            ->where('available', '<', 10)
            ->with('employee')
            ->orderBy('available', 'asc')
            ->limit(10)
            ->get();
    }

    public function getPendingApprovalsByLevel()
    {
        return [
            'pending_n1' => Leave::where('status', 'pending')->count(),
            'pending_n2' => Leave::where('status', 'approved_n1')->count(),
            'pending_final' => Leave::where('status', 'approved_n2')->count(),
        ];
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
                'month' => date('F', mktime(0, 0, 0, $i, 1)),
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
            12 => 'Décembre'
        ];

        return $months[$month] ?? '';
    }
}
