<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Exports\PayrollsExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportPayrolls extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Exporter Paie';
    protected static ?string $title = 'Exporter les Bulletins de Paie';
    protected static ?string $navigationGroup = '💰 Gestion de la Paie';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.export-payrolls';

    public $month;
    public $year;
    public $format = 'xlsx';

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
                ->default(now()->month),

            Select::make('year')
                ->label('Année')
                ->options(array_combine(range(2020, 2030), range(2020, 2030)))
                ->required()
                ->native(false)
                ->default(now()->year),

            Select::make('format')
                ->label('Format')
                ->options([
                    'xlsx' => 'Excel (XLSX)',
                    'csv' => 'CSV',
                ])
                ->required()
                ->native(false)
                ->default('xlsx'),
        ];
    }

    public function export()
    {
        $this->validate();

        try {
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

            $filename = 'Paie_' . $months[$this->month] . '_' . $this->year . '.' . $this->format;

            return Excel::download(
                new PayrollsExport($this->month, $this->year),
                $filename,
                $this->format === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->danger()
                ->body('Erreur lors de l\'export : ' . $e->getMessage())
                ->send();
        }
    }

    public function getStats()
    {
        $count = \App\Models\Payroll::where('month', $this->month ?? now()->month)
            ->where('year', $this->year ?? now()->year)
            ->count();

        $gross = \App\Models\Payroll::where('month', $this->month ?? now()->month)
            ->where('year', $this->year ?? now()->year)
            ->sum('gross_salary');

        $net = \App\Models\Payroll::where('month', $this->month ?? now()->month)
            ->where('year', $this->year ?? now()->year)
            ->sum('net_salary');

        return [
            'count' => $count,
            'gross' => $gross,
            'net' => $net,
        ];
    }
}
