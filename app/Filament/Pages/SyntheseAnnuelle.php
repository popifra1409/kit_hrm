<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Payroll;
use App\Models\Employee;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnnualSummaryExport;

class SyntheseAnnuelle extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Synthèse Annuelle';
    protected static ?string $title = 'Synthèse Annuelle des Retenues sur Salaires';
    protected static ?string $navigationGroup = '💰 Gestion de la Paie';
    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.synthese-annuelle';

    public $year;
    public $employee_id = null;

    public function mount(): void
    {
        $this->year = now()->year;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('year')
                ->label('Année')
                ->options(array_combine(range(2020, 2030), range(2020, 2030)))
                ->required()
                ->native(false)
                ->reactive(),

            Select::make('employee_id')
                ->label('Employé (optionnel - laisser vide pour tous)')
                ->options(function () {
                    return Employee::where('is_active', true)
                        ->orderBy('last_name')
                        ->get()
                        ->pluck('full_name', 'id');
                })
                ->searchable()
                ->nullable()
                ->native(false)
                ->reactive(),
        ];
    }

    public function getAnnualSummary()
    {
        $query = Payroll::with('employee')
            ->where('year', $this->year ?? now()->year);

        if ($this->employee_id) {
            $query->where('employee_id', $this->employee_id);
        }

        $payrolls = $query->get();

        // Regrouper par employé
        $byEmployee = $payrolls->groupBy('employee_id')->map(function ($employeePayrolls) {
            $employee = $employeePayrolls->first()->employee;

            return [
                'employee' => $employee,
                'matricule' => $employee->matricule,
                'full_name' => $employee->full_name,
                'cnps_number' => $employee->cnps_number,
                'qualification' => $employee->qualification,
                'months_paid' => $employeePayrolls->count(),

                // Totaux annuels
                'total_base_salary' => $employeePayrolls->sum('base_salary'),
                'total_gross_taxable' => $employeePayrolls->sum('gross_taxable'),
                'total_gross_cnps' => $employeePayrolls->sum('gross_cnps'),
                'total_gross_salary' => $employeePayrolls->sum('gross_salary'),

                // Retenues annuelles
                'total_cnps_employee' => $employeePayrolls->sum('cnps_employee'),
                'total_irpp' => $employeePayrolls->sum('irpp'),
                'total_cac' => $employeePayrolls->sum('cac'),
                'total_deductions' => $employeePayrolls->sum('total_deductions'),
                'total_net_salary' => $employeePayrolls->sum('net_salary'),

                // Détail mensuel
                'monthly_detail' => $employeePayrolls->sortBy('month')->values(),
            ];
        })->sortBy('full_name')->values();

        // Synthèse globale
        $globalSummary = [
            'total_employees' => $byEmployee->count(),
            'total_base_salary' => $byEmployee->sum('total_base_salary'),
            'total_gross_salary' => $byEmployee->sum('total_gross_salary'),
            'total_cnps_employee' => $byEmployee->sum('total_cnps_employee'),
            'total_cnps_employer' => $payrolls->sum('cnps_employer'),
            'total_cnps_total' => $byEmployee->sum('total_cnps_employee') + $payrolls->sum('cnps_employer'),
            'total_irpp' => $byEmployee->sum('total_irpp'),
            'total_cac' => $byEmployee->sum('total_cac'),
            'total_deductions' => $byEmployee->sum('total_deductions'),
            'total_net_salary' => $byEmployee->sum('total_net_salary'),
        ];

        // Répartition mensuelle
        $monthlyBreakdown = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthPayrolls = $payrolls->where('month', $month);
            $monthlyBreakdown[$month] = [
                'month_name' => $this->getMonthName($month),
                'count' => $monthPayrolls->count(),
                'gross' => $monthPayrolls->sum('gross_salary'),
                'cnps_employee' => $monthPayrolls->sum('cnps_employee'),
                'irpp' => $monthPayrolls->sum('irpp'),
                'net' => $monthPayrolls->sum('net_salary'),
            ];
        }

        return [
            'by_employee' => $byEmployee,
            'global_summary' => $globalSummary,
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    protected function getMonthName($month)
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

    public function exportExcel()
    {
        $filename = 'Synthese_Annuelle_Retenues_' . $this->year . '.xlsx';

        return Excel::download(
            new AnnualSummaryExport($this->year, $this->employee_id),
            $filename
        );
    }

    public function exportEmployeeDetail($employeeId)
    {
        $employee = Employee::find($employeeId);
        $filename = 'Retenues_' . $employee->matricule . '_' . $this->year . '.xlsx';

        return Excel::download(
            new AnnualSummaryExport($this->year, $employeeId),
            $filename
        );
    }
}
