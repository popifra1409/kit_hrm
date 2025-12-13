<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PayrollsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return Payroll::with(['employee'])
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom Complet',
            'Qualification',
            'Catégorie/Échelon',
            'Service',
            'Mois',
            'Année',
            'Salaire de Base',
            'Salaire Imposable',
            'Salaire Cotisable',
            'Salaire Brut',
            'CNPS Employé',
            'CNPS Employeur',
            'IRPP',
            'CAC',
            'Total Retenues',
            'Net à Payer',
            'Statut',
        ];
    }

    public function map($payroll): array
    {
        return [
            $payroll->employee->matricule,
            $payroll->employee->full_name,
            $payroll->employee->qualification,
            $payroll->employee->category_current,
            $payroll->employee->currentService?->name ?? 'N/A',
            $payroll->month_name,
            $payroll->year,
            $payroll->base_salary,
            $payroll->gross_taxable,
            $payroll->gross_cnps,
            $payroll->gross_salary,
            $payroll->cnps_employee,
            $payroll->cnps_employer,
            $payroll->irpp,
            $payroll->cac,
            $payroll->total_deductions,
            $payroll->net_salary,
            $this->getStatusLabel($payroll->status),
        ];
    }

    protected function getStatusLabel($status)
    {
        return match ($status) {
            'draft' => 'Brouillon',
            'validated' => 'Validé',
            'paid' => 'Payé',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour la première ligne (en-têtes)
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }

    public function title(): string
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

        return 'Paie_' . $months[$this->month] . '_' . $this->year;
    }
}
