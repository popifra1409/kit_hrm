<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MonthlySummaryExport;

class SyntheseMensuelle extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Synthèse Mensuelle';
    protected static ?string $title = 'Synthèse Mensuelle des Salaires';
    protected static ?string $navigationGroup = '💰 Gestion de la Paie';
    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.pages.synthese-mensuelle';

    public $month;
    public $year;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('month')
                ->label('Mois')
                ->options([
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
                ])
                ->required()
                ->native(false)
                ->reactive(),

            Select::make('year')
                ->label('Année')
                ->options(array_combine(range(2020, 2030), range(2020, 2030)))
                ->required()
                ->native(false)
                ->reactive(),
        ];
    }

    public function getSummaryData()
    {
        $payrolls = Payroll::with('employee')
            ->where('month', $this->month ?? now()->month)
            ->where('year', $this->year ?? now()->year)
            ->get();

        // Synthèse globale
        $summary = [
            'total_employees' => $payrolls->count(),
            'total_base_salary' => $payrolls->sum('base_salary'),
            'total_gross_taxable' => $payrolls->sum('gross_taxable'),
            'total_gross_cnps' => $payrolls->sum('gross_cnps'),
            'total_gross_salary' => $payrolls->sum('gross_salary'),
            'total_cnps_employee' => $payrolls->sum('cnps_employee'),
            'total_cnps_employer' => $payrolls->sum('cnps_employer'),
            'total_cnps_total' => $payrolls->sum('cnps_employee') + $payrolls->sum('cnps_employer'),
            'total_irpp' => $payrolls->sum('irpp'),
            'total_cac' => $payrolls->sum('cac'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net_salary' => $payrolls->sum('net_salary'),
        ];

        // Par département
        $byDepartment = $payrolls->groupBy(function ($payroll) {
            return $payroll->employee->currentService?->department?->name ?? 'Non assigné';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'gross' => $group->sum('gross_salary'),
                'net' => $group->sum('net_salary'),
                'cnps_employee' => $group->sum('cnps_employee'),
                'irpp' => $group->sum('irpp'),
            ];
        });

        // Par type de personnel
        $byPersonnelType = $payrolls->groupBy(function ($payroll) {
            return $payroll->employee->personnel_type === 'soignant' ? 'Soignant' : 'Non Soignant';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'gross' => $group->sum('gross_salary'),
                'net' => $group->sum('net_salary'),
            ];
        });

        return [
            'summary' => $summary,
            'by_department' => $byDepartment,
            'by_personnel_type' => $byPersonnelType,
        ];
    }

    public function exportExcel()
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

        $filename = 'Synthese_Mensuelle_' . $months[$this->month] . '_' . $this->year . '.xlsx';

        return Excel::download(
            new MonthlySummaryExport($this->month, $this->year),
            $filename
        );
    }
}
