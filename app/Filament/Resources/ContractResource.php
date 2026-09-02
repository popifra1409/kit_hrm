<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Filament\Traits\HasAuthorization;

class ContractResource extends Resource
{
    use HasAuthorization;
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = '📋 Contrats & Affectations';
    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Contrat Employé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Contrats Employés';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employé et Type de Contrat')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('Employé')
                                    ->relationship('employee', 'matricule')
                                    ->searchable()
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        $record->full_name . ' (' . $record->matricule . ')'
                                    )
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $employee = \App\Models\Employee::find($state);
                                            if ($employee && $employee->qualification) {
                                                $set('position', $employee->qualification->name ?? null);
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('contract_type_id')
                                    ->label('Type de Contrat')
                                    ->relationship('contractType', 'name')
                                    ->required()
                                    ->reactive()
                                    ->preload()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $contractType = \App\Models\ContractType::find($state);
                                        if ($contractType) {
                                            $set('requires_end_date', $contractType->requires_end_date);
                                        }
                                    }),

                                Forms\Components\TextInput::make('contract_number')
                                    ->label('Numéro de Contrat')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn() => Contract::generateContractNumber())
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),

                Forms\Components\Section::make('Dates du Contrat')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Date de Début')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Date de Fin')
                                    ->nullable()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Laisser vide pour un CDI')
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $startDate = $get('start_date');
                                        if ($startDate && $state) {
                                            $start = \Carbon\Carbon::parse($startDate);
                                            $end = \Carbon\Carbon::parse($state);
                                            $months = $start->diffInMonths($end);
                                            $set('duration_info', "{$months} mois");
                                        }
                                    })
                                    ->reactive(),

                                Forms\Components\DatePicker::make('signature_date')
                                    ->label('Date de Signature')
                                    ->nullable()
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                            ]),

                        Forms\Components\Placeholder::make('duration_info')
                            ->label('Durée du Contrat')
                            ->content(function (callable $get) {
                                $startDate = $get('start_date');
                                $endDate = $get('end_date');

                                if (!$endDate) {
                                    return '📌 CDI - Durée Indéterminée';
                                }

                                if ($startDate && $endDate) {
                                    $start = \Carbon\Carbon::parse($startDate);
                                    $end = \Carbon\Carbon::parse($endDate);
                                    $months = $start->diffInMonths($end);
                                    $years = floor($months / 12);
                                    $remainingMonths = $months % 12;

                                    if ($years > 0 && $remainingMonths > 0) {
                                        return "📅 {$years} an(s) et {$remainingMonths} mois";
                                    } elseif ($years > 0) {
                                        return "📅 {$years} an(s)";
                                    } else {
                                        return "📅 {$months} mois";
                                    }
                                }

                                return 'Sélectionnez les dates';
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Détails du Contrat')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('salary')
                                    ->label('Salaire Contractuel')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->placeholder('Ex: 500000')
                                    ->helperText('Salaire de base mensuel'),

                                Forms\Components\TextInput::make('position')
                                    ->label('Poste Occupé')
                                    ->maxLength(255)
                                    ->placeholder('Ex: Infirmier Chef'),
                            ]),

                        Forms\Components\Textarea::make('work_location')
                            ->label('Lieu de Travail')
                            ->rows(2)
                            ->placeholder('Service, département, site...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('terms')
                            ->label('Clauses Spécifiques du Contrat')
                            ->rows(4)
                            ->placeholder('Clauses particulières, conditions spéciales...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Statut du Contrat')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'draft' => '📝 Brouillon',
                                        'active' => '✅ Actif',
                                        'expired' => '⏰ Expiré',
                                        'terminated' => '❌ Résilié',
                                        'renewed' => '🔄 Renouvelé',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false)
                                    ->reactive(),

                                Forms\Components\Toggle::make('is_current')
                                    ->label('Contrat Actuel')
                                    ->default(true)
                                    ->helperText('Un seul contrat actuel par employé')
                                    ->inline(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Renouvellement')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('renewal_count')
                                    ->label('Nombre de Renouvellements')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Select::make('renewed_from_id')
                                    ->label('Renouvelé depuis le Contrat')
                                    ->relationship('renewedFrom', 'contract_number')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('Contrat précédent en cas de renouvellement'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Résiliation')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('termination_date')
                                    ->label('Date de Résiliation')
                                    ->nullable()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->visible(
                                        fn(callable $get) =>
                                        $get('status') === 'terminated'
                                    ),

                                Forms\Components\Textarea::make('termination_reason')
                                    ->label('Motif de Résiliation')
                                    ->rows(3)
                                    ->visible(
                                        fn(callable $get) =>
                                        $get('status') === 'terminated'
                                    )
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn(callable $get) => $get('status') === 'terminated'),

                Forms\Components\Section::make('Document du Contrat')
                    ->schema([
                        Forms\Components\FileUpload::make('document_path')
                            ->label('Document PDF du Contrat')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('contracts')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Notes et Observations')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes Internes')
                            ->rows(3)
                            ->placeholder('Remarques, observations...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('N° Contrat')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Contract $record) => $record->employee?->matricule),

                Tables\Columns\TextColumn::make('contractType.name')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('CDI')
                    ->description(
                        fn(Contract $record) =>
                        $record->remaining_days > 0
                            ? "⏰ {$record->remaining_days} jours restants"
                            : null
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'draft' => '📝 Brouillon',
                        'active' => '✅ Actif',
                        'expired' => '⏰ Expiré',
                        'terminated' => '❌ Résilié',
                        'renewed' => '🔄 Renouvelé',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'warning' => 'expired',
                        'danger' => 'terminated',
                        'info' => 'renewed',
                    ]),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('Actuel')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('salary')
                    ->label('Salaire')
                    ->money('XAF')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'active' => 'Actif',
                        'expired' => 'Expiré',
                        'terminated' => 'Résilié',
                        'renewed' => 'Renouvelé',
                    ]),

                Tables\Filters\SelectFilter::make('contract_type_id')
                    ->label('Type de Contrat')
                    ->relationship('contractType', 'name'),

                Tables\Filters\TernaryFilter::make('is_current')
                    ->label('Contrat Actuel')
                    ->placeholder('Tous')
                    ->trueLabel('Actuels uniquement')
                    ->falseLabel('Anciens uniquement'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expirant Bientôt')
                    ->query(fn($query) => $query->expiringSoon(30)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->visible(fn($record) => static::can('view', $record)),
                    Tables\Actions\EditAction::make()->visible(fn($record) => static::can('update', $record)),

                    Tables\Actions\Action::make('validate')
                        ->label('Valider')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(
                            fn(Contract $record) =>
                            $record->status === 'draft' &&
                                auth()->user()->can('validate', $record)
                        )
                        ->action(function (Contract $record) {
                            $record->validate();

                            Notification::make()
                                ->success()
                                ->title('Contrat validé')
                                ->body("Le contrat {$record->contract_number} a été activé.")
                                ->send();
                        }),

                    Tables\Actions\Action::make('renew')
                        ->label('Renouveler')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(
                            fn(Contract $record) =>
                            $record->canBeRenewed() &&
                                auth()->user()->can('renew', $record)
                        )
                        ->form([
                            Forms\Components\DatePicker::make('new_end_date')
                                ->label('Nouvelle Date de Fin')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->minDate(now()),

                            Forms\Components\TextInput::make('new_salary')
                                ->label('Nouveau Salaire (optionnel)')
                                ->numeric()
                                ->prefix('FCFA')
                                ->placeholder('Laisser vide pour garder le salaire actuel'),
                        ])
                        ->action(function (Contract $record, array $data) {
                            $newContract = $record->renew(
                                $data['new_end_date'],
                                $data['new_salary'] ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title('Contrat renouvelé')
                                ->body("Nouveau contrat créé : {$newContract->contract_number}")
                                ->send();
                        }),

                    Tables\Actions\Action::make('terminate')
                        ->label('Résilier')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(
                            fn(Contract $record) =>
                            $record->status === 'active' &&
                                auth()->user()->can('terminate', $record)
                        )
                        ->form([
                            Forms\Components\DatePicker::make('termination_date')
                                ->label('Date de Résiliation')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now()),

                            Forms\Components\Textarea::make('termination_reason')
                                ->label('Motif de Résiliation')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Contract $record, array $data) {
                            $record->terminate(
                                $data['termination_reason'],
                                $data['termination_date']
                            );

                            Notification::make()
                                ->success()
                                ->title('Contrat résilié')
                                ->body("Le contrat {$record->contract_number} a été résilié.")
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()->visible(fn($record) => static::can('delete', $record)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::expiringSoon(30)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::expiringSoon(30)->count();
        return $count > 0 ? 'warning' : null;
    }
}
