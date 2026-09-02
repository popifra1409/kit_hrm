<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ✅ EXPORT CSV (Compatible UTF-8)
            Actions\Action::make('export_csv')
                ->label('CSV')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->tooltip('Exporter en CSV')
                ->action(fn() => $this->exportCsv()),

            // ✅ EXPORT EXCEL
            Actions\Action::make('export_excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->tooltip('Exporter en Excel')
                ->action(fn() => $this->exportExcel()),

            Actions\CreateAction::make()
                ->label('Nouvel Employe')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->icon('heroicon-o-users')
                ->badge(fn() => \App\Models\Employee::count()),

            'active' => Tab::make('Actifs')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_active', true))
                ->badge(fn() => \App\Models\Employee::where('is_active', true)->count())
                ->badgeColor('success'),

            'soignant' => Tab::make('Personnel Soignant')
                ->icon('heroicon-o-heart')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('personnel_type', 'soignant'))
                ->badge(fn() => \App\Models\Employee::where('personnel_type', 'soignant')->count())
                ->badgeColor('success'),

            'non_soignant' => Tab::make('Personnel Non-Soignant')
                ->icon('heroicon-o-briefcase')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('personnel_type', 'non_soignant'))
                ->badge(fn() => \App\Models\Employee::where('personnel_type', 'non_soignant')->count())
                ->badgeColor('primary'),

            'managers' => Tab::make('Cadres de Management')
                ->icon('heroicon-o-user-group')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereHas('jobTitle', fn($q) => $q->where('is_managerial', true))
                )
                ->badge(fn() => \App\Models\Employee::whereHas(
                    'jobTitle',
                    fn($q) => $q->where('is_managerial', true)
                )->count())
                ->badgeColor('warning'),

            'inactive' => Tab::make('Inactifs')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_active', false))
                ->badge(fn() => \App\Models\Employee::where('is_active', false)->count())
                ->badgeColor('danger'),
        ];
    }

    // ✅ EXPORT CSV - Robuste pour UTF-8
    private function exportCsv()
    {
        $employees = \App\Models\Employee::with(['jobTitle', 'currentService', 'department'])->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=employes-' . now()->format('Y-m-d-His') . '.csv',
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');

            // BOM pour Excel UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // En-têtes
            $headers = [
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
                'Statut'
            ];
            fputcsv($file, $headers, ',');

            // Données
            foreach ($employees as $employee) {
                $row = [
                    $employee->id,
                    $employee->matricule ?? '',
                    $employee->last_name ?? '',
                    $employee->first_name ?? '',
                    $employee->gender === 'M' ? 'M' : ($employee->gender === 'F' ? 'F' : ''),
                    $employee->birth_date?->format('d/m/Y') ?? '',
                    $employee->jobTitle?->name ?? '',
                    $employee->currentService?->name ?? '',
                    $employee->department?->name ?? '',
                    $employee->classification_type === 'cameroon' ? 'Cameroon' : 'Numerique',
                    $employee->category_recruitment ?? '',
                    $employee->category_number ?? '',
                    $employee->echelon_number ?? '',
                    $employee->indice ?? '',
                    $employee->recruitment_date?->format('d/m/Y') ?? '',
                    $employee->retirement_date?->format('d/m/Y') ?? '',
                    $employee->is_active ? 'Actif' : 'Inactif',
                ];
                fputcsv($file, $row, ',');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ✅ EXPORT EXCEL
    private function exportExcel()
    {
        return \Excel::download(
            new \App\Exports\EmployeesExport(),
            'employes-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }
}
