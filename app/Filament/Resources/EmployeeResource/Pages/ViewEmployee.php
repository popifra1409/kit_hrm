<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifier'),

            Actions\Action::make('generate_professional_card')
                ->label('Générer Carte Pro')
                ->icon('heroicon-o-identification')
                ->color('info')
                ->visible(fn($record) => !$record->hasActiveProfessionalCard())
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;

                    try {
                        $card = \App\Models\EmployeeCard::create([
                            'employee_id' => $record->id,
                            'card_type' => 'professional',
                            'issue_date' => now(),
                            'expiry_date' => now()->addYears(5),
                            'status' => 'issued',
                        ]);

                        $card->generateCardNumber();
                        $card->generateQrCode();

                        if (class_exists(\App\Services\CardPdfService::class)) {
                            $pdfService = new \App\Services\CardPdfService();
                            $pdfPath = $pdfService->generateProfessionalCard($card);
                        }

                        $card->activate();

                        \Filament\Notifications\Notification::make()
                            ->title('Carte créée')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erreur')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations Générales')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\View::make('filament.infolists.employee-photo')
                                    ->viewData(['record' => $this->record]),

                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('matricule')
                                        ->label('Matricule')
                                        ->badge()
                                        ->color('primary')
                                        ->size('lg')
                                        ->weight('bold'),

                                    Infolists\Components\TextEntry::make('full_name')
                                        ->label('Nom Complet')
                                        ->size('xl')
                                        ->weight('bold'),

                                    Infolists\Components\TextEntry::make('gender')
                                        ->label('Sexe')
                                        ->formatStateUsing(fn($state) => $state === 'M' ? '👨 Masculin' : '👩 Féminin'),

                                    Infolists\Components\TextEntry::make('qualification.name')
                                        ->label('Qualification')
                                        ->badge()
                                        ->color('info'),
                                ])
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Structure Organisationnelle')
                    ->schema([
                        Infolists\Components\TextEntry::make('hierarchy_path')
                            ->label('Chemin Hiérarchique Complet')
                            ->badge()
                            ->color('success')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('branch')
                            ->label('Branche')
                            ->getStateUsing(
                                fn($record) =>
                                $record->isMedical() ? '🏥 Médicale' : '🏢 Administrative'
                            )
                            ->badge()
                            ->color(fn($record) => $record->isMedical() ? 'success' : 'primary'),

                        Infolists\Components\TextEntry::make('department.name')
                            ->label('Département')
                            ->visible(fn($record) => $record->department_id),

                        Infolists\Components\TextEntry::make('currentService.name')
                            ->label('Service'),

                        Infolists\Components\TextEntry::make('sector.name')
                            ->label('Secteur / Unité')
                            ->visible(fn($record) => $record->sector_id),

                        Infolists\Components\TextEntry::make('tradeBody.name')
                            ->label('Corps de Métier')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('jobTitle.name')
                            ->label('Poste Hiérarchique')
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('jobTitle.hierarchy_level')
                            ->label('Niveau Hiérarchique')
                            ->badge()
                            ->color('warning'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Informations Professionnelles')
                    ->schema([
                        Infolists\Components\TextEntry::make('administrative_status')
                            ->label('Statut Administratif')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'fonctionnaire_affecte' => '🏛️ Fonctionnaire Affecté',
                                'fonctionnaire_detache' => '🔄 Fonctionnaire en Détachement',
                                'contractuel_fp' => '📋 Contractuel de la Fonction Publique',
                                'contractuel_structure' => '🏥 Contractuel de la Structure',
                                'stagiaire' => '🎓 Stagiaire',
                                default => 'Non défini',
                            })
                            ->badge(),

                        Infolists\Components\TextEntry::make('personnel_type')
                            ->label('Type de Personnel')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'soignant' => '👨‍⚕️ Soignant',
                                'non_soignant' => '💼 Non-Soignant',
                                'paramedical' => '🩺 Paramédical',
                                'autres' => '🔧 Autres',
                                default => $state,
                            })
                            ->badge(),

                        Infolists\Components\TextEntry::make('recruitment_date')
                            ->label('Date de Recrutement')
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('anciennete')
                            ->label('Ancienneté')
                            ->getStateUsing(fn($record) => $record->anciennete . ' ans')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('retirement_date')
                            ->label('Date de Retraite')
                            ->date('d/m/Y'),
                    ])
                    ->columns(3),

                // ✅ SECTION CLASSIFICATION SALARIALE - ADAPTÉE
                Infolists\Components\Section::make('Classification Salariale')
                    ->schema([
                        Infolists\Components\TextEntry::make('classification_type')
                            ->label('Type de Classification')
                            ->formatStateUsing(fn($state) => $state === 'cameroon' ? '🇨🇲 Nomenclature Camerounaise' : '🔢 Classification Numérique')
                            ->badge()
                            ->color(fn($state) => $state === 'cameroon' ? 'info' : 'warning'),

                        // ✅ AFFICHAGE CAMEROUNAIS
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('category_number')
                                ->label('Catégorie')
                                ->badge()
                                ->color('primary')
                                ->size('lg')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('echelon_number')
                                ->label('Échelon')
                                ->badge()
                                ->color('success')
                                ->size('lg')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('indice')
                                ->label('Indice')
                                ->badge()
                                ->color('warning'),
                        ])
                            ->visible(fn($record) => $record->classification_type === 'cameroon')
                            ->columns(3),

                        // ✅ AFFICHAGE NUMÉRIQUE
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('category_number')
                                ->label('Catégorie')
                                ->suffix(' / 12')
                                ->badge()
                                ->color('primary')
                                ->size('lg')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('echelon_number')
                                ->label('Échelon')
                                ->suffix(' / 12')
                                ->badge()
                                ->color('success')
                                ->size('lg')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('indice')
                                ->label('Indice')
                                ->badge()
                                ->color('warning'),
                        ])
                            ->visible(fn($record) => $record->classification_type === 'numeric')
                            ->columns(3),

                        Infolists\Components\TextEntry::make('category_recruitment')
                            ->label('Catégorie de Recrutement'),

                        Infolists\Components\TextEntry::make('echelon_start_date')
                            ->label('Début Échelon Actuel')
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('last_advancement_date')
                            ->label('Dernier Avancement')
                            ->date('d/m/Y'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Coordonnées')
                    ->schema([
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Téléphone')
                            ->icon('heroicon-o-phone'),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('address')
                            ->label('Adresse')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('city')
                            ->label('Ville'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('QR Code')
                    ->schema([
                        Infolists\Components\View::make('filament.infolists.qr-code-display')
                            ->viewData(['record' => $this->record])
                            ->visible(fn($record) => $record->qr_code_path),
                    ])
                    ->collapsible()
                    ->visible(fn($record) => $record->qr_code_path),
            ]);
    }
}
