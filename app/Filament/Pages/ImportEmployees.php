<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Imports\EmployeesImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportEmployees extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Importer Employés';
    protected static ?string $title = 'Importer des Employés depuis Excel';
    protected static ?string $navigationGroup = '👥 Gestion du Personnel';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.import-employees';

    public $file;

    protected function getFormSchema(): array
    {
        return [
            FileUpload::make('file')
                ->label('Fichier Excel')
                ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                ->required()
                ->helperText('Sélectionnez le fichier Excel contenant les employés'),
        ];
    }

    public function import()
    {
        $this->validate();

        try {
            Excel::import(new EmployeesImport, $this->file);

            Notification::make()
                ->title('Import réussi !')
                ->success()
                ->body('Les employés ont été importés avec succès.')
                ->send();

            $this->file = null;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur d\'import')
                ->danger()
                ->body('Erreur: ' . $e->getMessage())
                ->send();
        }
    }
}
