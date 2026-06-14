<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Employee::with(['position', 'currentService', 'department'])
            ->get()
            ->map(function ($employee) {
                return [
                    $employee->id,
                    $employee->matricule ?? '-',
                    $employee->last_name ?? '-',
                    $employee->first_name ?? '-',
                    $employee->gender === 'M' ? 'M' : ($employee->gender === 'F' ? 'F' : '-'),
                    $employee->birth_date?->format('d/m/Y') ?? '-',
                    $employee->position?->name ?? '-',
                    $employee->currentService?->name ?? '-',
                    $employee->department?->name ?? '-',
                    $employee->classification_type === 'cameroon' ? 'Cameroon' : 'Numerique',
                    $employee->category_recruitment ?? '-',
                    $employee->category_number ?? '-',
                    $employee->echelon_number ?? '-',
                    $employee->indice ?? '-',
                    $employee->recruitment_date?->format('d/m/Y') ?? '-',
                    $employee->retirement_date?->format('d/m/Y') ?? '-',
                    $employee->is_active ? 'Actif' : 'Inactif',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Matricule',
            'Nom',
            'Prenom',
            'Sexe',
            'Date Naissance',
            'Poste',
            'Service',
            'Departement',
            'Type Classification',
            'Categorie Recrutement',
            'Categorie Actuelle',
            'Echelon Actuel',
            'Indice',
            'Date Recrutement',
            'Date Retraite',
            'Statut',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4B5563']],
            ],
        ];
    }
}
