<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),

            Actions\Action::make('validate')
                ->label('Valider le Contrat')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status === 'draft')
                ->action(function () {
                    $this->record->validate();

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Contrat validé')
                        ->body("Le contrat {$this->record->contract_number} a été activé.")
                        ->send();

                    $this->refreshFormData(['status', 'validated_at', 'validated_by']);
                }),

            Actions\Action::make('renew')
                ->label('Renouveler')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn() => $this->record->canBeRenewed())
                ->form([
                    Infolists\Components\TextEntry::make('info')
                        ->label('')
                        ->html()
                        ->default(
                            fn() =>
                            '<div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="font-bold text-blue-900">Renouvellement du contrat</p>
                                <p class="text-sm text-blue-700 mt-2">Contrat actuel : ' . $this->record->contract_number . '</p>
                                <p class="text-sm text-blue-700">Fin actuelle : ' . $this->record->end_date->format('d/m/Y') . '</p>
                            </div>'
                        ),

                    \Filament\Forms\Components\DatePicker::make('new_end_date')
                        ->label('Nouvelle Date de Fin')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->minDate($this->record->end_date->addDay()),

                    \Filament\Forms\Components\TextInput::make('new_salary')
                        ->label('Nouveau Salaire (optionnel)')
                        ->numeric()
                        ->prefix('FCFA')
                        ->placeholder('Laisser vide pour garder : ' . number_format($this->record->salary, 0, ',', ' ') . ' FCFA'),
                ])
                ->action(function (array $data) {
                    $newContract = $this->record->renew(
                        $data['new_end_date'],
                        $data['new_salary'] ?? null
                    );

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Contrat renouvelé')
                        ->body("Nouveau contrat créé : {$newContract->contract_number}")
                        ->send();

                    return redirect()->route('filament.admin.resources.contracts.view', ['record' => $newContract]);
                }),

            Actions\Action::make('terminate')
                ->label('Résilier')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status === 'active')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('termination_date')
                        ->label('Date de Résiliation')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->default(now()),

                    \Filament\Forms\Components\Textarea::make('termination_reason')
                        ->label('Motif de Résiliation')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->terminate(
                        $data['termination_reason'],
                        $data['termination_date']
                    );

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Contrat résilié')
                        ->body("Le contrat {$this->record->contract_number} a été résilié.")
                        ->send();

                    $this->refreshFormData(['status', 'termination_date', 'termination_reason']);
                }),

            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // SECTION : Aperçu Général
                Infolists\Components\Section::make('Aperçu Général')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('contract_number')
                                    ->label('Numéro de Contrat')
                                    ->badge()
                                    ->color('primary')
                                    ->size('lg')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'draft' => '📝 Brouillon',
                                        'active' => '✅ Actif',
                                        'expired' => '⏰ Expiré',
                                        'terminated' => '❌ Résilié',
                                        'renewed' => '🔄 Renouvelé',
                                        default => $state,
                                    })
                                    ->color(fn($state) => match ($state) {
                                        'draft' => 'gray',
                                        'active' => 'success',
                                        'expired' => 'warning',
                                        'terminated' => 'danger',
                                        'renewed' => 'info',
                                        default => 'gray',
                                    }),

                                Infolists\Components\IconEntry::make('is_current')
                                    ->label('Contrat Actuel')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->size('lg'),
                            ]),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary'),

                // SECTION : Employé
                Infolists\Components\Section::make('Employé')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('employee.full_name')
                                    ->label('Nom Complet')
                                    ->weight('bold')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('employee.matricule')
                                    ->label('Matricule')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('employee.qualification.name')
                                    ->label('Qualification Actuelle')
                                    ->placeholder('Non défini'),
                            ]),
                    ])
                    ->icon('heroicon-o-user')
                    ->iconColor('success')
                    ->collapsible(),

                // SECTION : Détails du Contrat
                Infolists\Components\Section::make('Détails du Contrat')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('contractType.name')
                                    ->label('Type de Contrat')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('position')
                                    ->label('Poste Contractuel')
                                    ->placeholder('Non spécifié'),

                                Infolists\Components\TextEntry::make('salary')
                                    ->label('Salaire Contractuel')
                                    ->money('XAF')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),

                        Infolists\Components\TextEntry::make('work_location')
                            ->label('Lieu de Travail')
                            ->placeholder('Non spécifié')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-briefcase')
                    ->iconColor('warning')
                    ->collapsible(),

                // SECTION : Dates
                Infolists\Components\Section::make('Période du Contrat')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('start_date')
                                    ->label('Date de Début')
                                    ->date('d/m/Y')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('end_date')
                                    ->label('Date de Fin')
                                    ->date('d/m/Y')
                                    ->placeholder('CDI - Indéterminée')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('signature_date')
                                    ->label('Date de Signature')
                                    ->date('d/m/Y')
                                    ->placeholder('Non signée')
                                    ->icon('heroicon-o-pencil'),

                                Infolists\Components\TextEntry::make('formatted_duration')
                                    ->label('Durée Totale')
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-clock'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('remaining_days')
                                    ->label('Jours Restants')
                                    ->badge()
                                    ->color(
                                        fn($state) =>
                                        $state > 30 ? 'success' : ($state > 0 ? 'warning' : 'danger')
                                    )
                                    ->suffix(' jours')
                                    ->visible(fn($record) => $record->end_date && !$record->isExpired()),

                                Infolists\Components\TextEntry::make('is_expiring_soon')
                                    ->label('Alerte')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state ? '⚠️ Expire Bientôt' : '✅ OK')
                                    ->color(fn($state) => $state ? 'warning' : 'success')
                                    ->visible(fn($record) => $record->end_date),
                            ]),
                    ])
                    ->icon('heroicon-o-calendar-days')
                    ->iconColor('info')
                    ->collapsible(),

                // SECTION : Renouvellement
                Infolists\Components\Section::make('Historique de Renouvellement')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('renewal_count')
                                    ->label('Nombre de Renouvellements')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('renewedFrom.contract_number')
                                    ->label('Renouvelé depuis')
                                    ->placeholder('Contrat initial')
                                    ->url(
                                        fn($record) =>
                                        $record->renewed_from_id
                                            ? route('filament.admin.resources.contracts.view', ['record' => $record->renewed_from_id])
                                            : null
                                    )
                                    ->color('primary'),
                            ]),
                    ])
                    ->icon('heroicon-o-arrow-path')
                    ->iconColor('info')
                    ->visible(fn($record) => $record->renewal_count > 0 || $record->renewed_from_id)
                    ->collapsible()
                    ->collapsed(),

                // SECTION : Résiliation
                Infolists\Components\Section::make('Résiliation')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('termination_date')
                                    ->label('Date de Résiliation')
                                    ->date('d/m/Y')
                                    ->icon('heroicon-o-calendar')
                                    ->color('danger'),

                                Infolists\Components\TextEntry::make('termination_reason')
                                    ->label('Motif de Résiliation')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->icon('heroicon-o-x-circle')
                    ->iconColor('danger')
                    ->visible(fn($record) => $record->status === 'terminated')
                    ->collapsible(),

                // SECTION : Clauses
                Infolists\Components\Section::make('Clauses Contractuelles')
                    ->schema([
                        Infolists\Components\TextEntry::make('terms')
                            ->label('Clauses Spécifiques')
                            ->placeholder('Aucune clause spécifique')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->visible(fn($record) => !empty($record->terms))
                    ->collapsible()
                    ->collapsed(),

                // SECTION : Document
                Infolists\Components\Section::make('Document')
                    ->schema([
                        Infolists\Components\TextEntry::make('document_path')
                            ->label('Document PDF')
                            ->formatStateUsing(fn($state) => $state ? '📄 Document disponible' : 'Aucun document')
                            ->color(fn($state) => $state ? 'success' : 'gray')
                            ->url(fn($record) => $record->document_path ? asset('storage/' . $record->document_path) : null)
                            ->openUrlInNewTab(),
                    ])
                    ->icon('heroicon-o-document')
                    ->iconColor('primary')
                    ->visible(fn($record) => !empty($record->document_path))
                    ->collapsible()
                    ->collapsed(),

                // SECTION : Validation
                Infolists\Components\Section::make('Validation')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('validator.name')
                                    ->label('Validé par')
                                    ->placeholder('Non validé'),

                                Infolists\Components\TextEntry::make('validated_at')
                                    ->label('Date de Validation')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Non validé'),
                            ]),
                    ])
                    ->icon('heroicon-o-check-badge')
                    ->iconColor('success')
                    ->visible(fn($record) => $record->validated_at)
                    ->collapsible()
                    ->collapsed(),

                // SECTION : Notes
                Infolists\Components\Section::make('Notes Internes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Aucune note')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->visible(fn($record) => !empty($record->notes))
                    ->collapsible()
                    ->collapsed(),

                // SECTION : Métadonnées
                Infolists\Components\Section::make('Informations Système')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->dateTime('d/m/Y H:i'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Modifié le')
                                    ->dateTime('d/m/Y H:i'),

                                Infolists\Components\TextEntry::make('deleted_at')
                                    ->label('Supprimé le')
                                    ->dateTime('d/m/Y H:i')
                                    ->visible(fn($record) => $record->deleted_at),
                            ]),
                    ])
                    ->icon('heroicon-o-information-circle')
                    ->iconColor('gray')
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
