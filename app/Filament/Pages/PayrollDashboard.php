<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollItem;

class PayrollDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Tableau de Bord Paie';
    protected static ?string $title = 'Tableau de Bord - Gestion de la Paie';
    protected static ?string $navigationGroup = '💰 Gestion de la Paie';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.payroll-dashboard';

    public function getStats()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        return [
            // Bulletins du mois en cours
            'current_month' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->count(),

            // Bulletins validés ce mois
            'validated_month' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 'validated')
                ->count(),

            // Bulletins payés ce mois
            'paid_month' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 'paid')
                ->count(),

            // Masse salariale du mois (brut)
            'gross_payroll' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->sum('gross_salary'),

            // Masse salariale du mois (net)
            'net_payroll' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->sum('net_salary'),

            // Total CNPS employé
            'cnps_employee' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->sum('cnps_employee'),

            // Total CNPS employeur
            'cnps_employer' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->sum('cnps_employer'),

            // Total IRPP
            'irpp' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->sum('irpp'),
        ];
    }

    public function getPayrollByStatus()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        return [
            'draft' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 'draft')
                ->count(),

            'validated' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 'validated')
                ->count(),

            'paid' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 'paid')
                ->count(),

            'cancelled' => Payroll::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 'cancelled')
                ->count(),
        ];
    }

    public function getMonthlyTrend()
    {
        $currentYear = now()->year;
        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            $gross = Payroll::where('year', $currentYear)
                ->where('month', $i)
                ->sum('gross_salary');

            $net = Payroll::where('year', $currentYear)
                ->where('month', $i)
                ->sum('net_salary');

            $months[] = [
                'month' => $i,
                'month_name' => $this->getMonthNameFr($i),
                'gross' => $gross,
                'net' => $net,
                'count' => Payroll::where('year', $currentYear)
                    ->where('month', $i)
                    ->count(),
            ];
        }

        return $months;
    }

    public function getTopSalaries()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        return Payroll::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->with('employee')
            ->orderByDesc('net_salary')
            ->limit(10)
            ->get();
    }

    public function getPayrollByDepartment()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        return Payroll::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->whereHas('employee')
            ->with(['employee.currentService.department'])
            ->get()
            ->groupBy(function ($payroll) {
                return $payroll->employee->currentService?->department?->name ?? 'Non assigné';
            })
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'gross' => $group->sum('gross_salary'),
                    'net' => $group->sum('net_salary'),
                ];
            });
    }

    public function getCotisationsBreakdown()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $payrolls = Payroll::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->get();

        return [
            'cnps_employee' => $payrolls->sum('cnps_employee'),
            'cnps_employer' => $payrolls->sum('cnps_employer'),
            'cnps_total' => $payrolls->sum('cnps_employee') + $payrolls->sum('cnps_employer'),
            'irpp' => $payrolls->sum('irpp'),
            'cac' => $payrolls->sum('cac'),
        ];
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
