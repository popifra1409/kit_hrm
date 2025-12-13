<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnnualSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $year;
    protected $employeeId;

    public function __construct($year, $employeeId = null)
    {
        $this->year = $year;
        $this->employeeId = $employeeId;
    }

    public function collection()
    {
        $query = Payroll::with('employee')
            ->where('year', $this->year);

        if ($this->employeeId) {
            $query->where('employee_id', $this->employeeId);
        }

        return $query->orderBy('employee_id')
            ->orderBy('month')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom Complet',
            'N° CNPS',
            'Mois',
            'Année',
            'Salaire de Base',
            'Salaire Brut',
            'CNPS Employé',
            'CNPS Employeur',
            'IRPP',
            'CAC',
            'Taxe Communale',
            'Total Retenues',
            'Net à Payer',
        ];
    }

    public function map($payroll): array
    {
        return [
            $payroll->employee->matricule,
            $payroll->employee->full_name,
            $payroll->employee->cnps_number,
            $payroll->month_name,
            $payroll->year,
            $payroll->base_salary,
            $payroll->gross_salary,
            $payroll->cnps_employee,
            $payroll->cnps_employer,
            $payroll->irpp,
            $payroll->cac,
            $payroll->lines()->whereHas('payrollItem', fn($q) => $q->where('code', 'TDL'))->sum('amount'),
            $payroll->total_deductions,
            $payroll->net_salary,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Synthese_Annuelle_' . $this->year;
    }
}
