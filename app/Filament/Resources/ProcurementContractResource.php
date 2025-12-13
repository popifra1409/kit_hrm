<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcurementContractResource\Pages;
use App\Models\ProcurementContract;
use App\Models\Procurement;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProcurementContractResource extends Resource
{
    protected static ?string $model = ProcurementContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public static function getModelLabel(): string
    {
        return 'Contrat';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Contrats';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏗️ Marchés Publics';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informations de Base')
                        ->schema([
                            Forms\Components\Select::make('procurement_id')
                                ->label('Marché Public')
                                ->options(Procurement::where('status', 'awarded')
                                    ->doesntHave('contract')
                                    ->orderBy('reference', 'desc')
                                    ->get()
                                    ->pluck('reference', 'id'))
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->native(false)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $procurement = Procurement::with('awardedSupplier')->find($state);
                                        if ($procurement) {
                                            $set('supplier_id', $procurement->awarded_supplier_id);
                                            $set('contract_amount', $procurement->awarded_amount);

                                            // Calculer TVA
                                            $ht = $procurement->awarded_amount;
                                            $vat = $ht * 0.1925;
                                            $ttc = $ht + $vat;

                                            $set('vat_amount', round($vat, 2));
                                            $set('total_amount', round($ttc, 2));
                                        }
                                    }
                                }),

                            Forms\Components\Select::make('supplier_id')
                                ->label('Fournisseur')
                                ->options(Supplier::where('status', 'active')->pluck('name', 'id'))
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->searchable()
                                ->native(false),

                            Forms\Components\TextInput::make('contract_number')
                                ->label('Numéro de Contrat')
                                ->default(fn() => ProcurementContract::generateContractNumber())
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ])
                        ->columns(3),

                    Forms\Components\Wizard\Step::make('Montants')
                        ->schema([
                            Forms\Components\TextInput::make('contract_amount')
                                ->label('Montant du Contrat (HT)')
                                ->required()
                                ->numeric()
                                ->prefix('FCFA')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $ht = $state ?? 0;
                                    $vat = $ht * 0.1925;
                                    $ttc = $ht + $vat;

                                    $set('vat_amount', round($vat, 2));
                                    $set('total_amount', round($ttc, 2));
                                }),

                            Forms\Components\TextInput::make('vat_amount')
                                ->label('TVA (19.25%)')
                                ->numeric()
                                ->prefix('FCFA')
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\TextInput::make('total_amount')
                                ->label('Montant Total TTC')
                                ->numeric()
                                ->prefix('FCFA')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(3),

                    Forms\Components\Wizard\Step::make('Garanties et Avances')
                        ->schema([
                            Forms\Components\TextInput::make('performance_bond')
                                ->label('Caution de Bonne Exécution')
                                ->numeric()
                                ->prefix('FCFA')
                                ->helperText('Généralement 5-10% du montant du contrat'),

                            Forms\Components\TextInput::make('advance_payment')
                                ->label('Avance de Démarrage')
                                ->numeric()
                                ->prefix('FCFA')
                                ->helperText('Maximum 20% du montant du contrat'),

                            Forms\Components\TextInput::make('warranty_period_months')
                                ->label('Période de Garantie')
                                ->numeric()
                                ->suffix('mois')
                                ->default(12)
                                ->helperText('Durée de la garantie après réception'),
                        ])
                        ->columns(3),

                    Forms\Components\Wizard\Step::make('Calendrier')
                        ->schema([
                            Forms\Components\DatePicker::make('signature_date')
                                ->label('Date de Signature')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now()),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('Date de Début')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $days = $get('duration_days');
                                    if ($state && $days) {
                                        $endDate = \Carbon\Carbon::parse($state)->addDays($days);
                                        $set('end_date', $endDate->format('Y-m-d'));
                                    }
                                }),

                            Forms\Components\TextInput::make('duration_days')
                                ->label('Durée (jours)')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $startDate = $get('start_date');
                                    if ($startDate && $state) {
                                        $endDate = \Carbon\Carbon::parse($startDate)->addDays($state);
                                        $set('end_date', $endDate->format('Y-m-d'));
                                    }
                                }),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('Date de Fin')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Représentants')
                        ->schema([
                            Forms\Components\Textarea::make('chuy_representative')
                                ->label('Représentant CHUY')
                                ->rows(3)
                                ->placeholder('Nom, Fonction, Signature')
                                ->helperText('Informations du signataire pour le CHUY'),

                            Forms\Components\Textarea::make('supplier_representative')
                                ->label('Représentant Fournisseur')
                                ->rows(3)
                                ->placeholder('Nom, Fonction, Signature')
                                ->helperText('Informations du signataire pour le fournisseur'),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Documents')
                        ->schema([
                            Forms\Components\FileUpload::make('contract_document_path')
                                ->label('Document de Contrat (Projet)')
                                ->directory('contracts/drafts')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(10240)
                                ->helperText('Version brouillon du contrat'),

                            Forms\Components\FileUpload::make('signed_contract_path')
                                ->label('Contrat Signé')
                                ->directory('contracts/signed')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(10240)
                                ->helperText('Version signée et scannée du contrat'),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Statut')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Statut du Contrat')
                                ->options([
                                    'draft' => 'Brouillon',
                                    'pending_signature' => 'En Attente de Signature',
                                    'signed' => 'Signé',
                                    'in_execution' => 'En Exécution',
                                    'completed' => 'Terminé',
                                    'suspended' => 'Suspendu',
                                    'terminated' => 'Résilié',
                                ])
                                ->default('draft')
                                ->required()
                                ->native(false),

                            Forms\Components\Textarea::make('notes')
                                ->label('Notes et Observations')
                                ->rows(4)
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
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
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('procurement.reference')
                    ->label('Marché')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Montant TTC')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('signature_date')
                    ->label('Date Signature')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Durée')
                    ->suffix(' j')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_signature',
                        'success' => fn($state) => in_array($state, ['signed', 'in_execution']),
                        'primary' => 'completed',
                        'danger' => fn($state) => in_array($state, ['suspended', 'terminated']),
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'pending_signature' => 'En Signature',
                        'signed' => 'Signé',
                        'in_execution' => 'En Exécution',
                        'completed' => 'Terminé',
                        'suspended' => 'Suspendu',
                        'terminated' => 'Résilié',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'pending_signature' => 'En Signature',
                        'signed' => 'Signé',
                        'in_execution' => 'En Exécution',
                        'completed' => 'Terminé',
                        'suspended' => 'Suspendu',
                        'terminated' => 'Résilié',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Fournisseur')
                    ->options(Supplier::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),

                Tables\Actions\Action::make('amendment')
                    ->label('Avenant')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->visible(fn($record) => in_array($record->status, ['signed', 'in_execution']))
                    ->url(fn($record) => route('filament.admin.resources.procurement-contracts.amendment', $record)),

                Tables\Actions\Action::make('execution')
                    ->label('Suivi')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->visible(fn($record) => $record->status === 'in_execution')
                    ->url(fn($record) => route('filament.admin.resources.procurement-contracts.execution', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
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
            'index' => Pages\ListProcurementContracts::route('/'),
            'create' => Pages\CreateProcurementContract::route('/create'),
            'edit' => Pages\EditProcurementContract::route('/{record}/edit'),
        ];
    }
}
