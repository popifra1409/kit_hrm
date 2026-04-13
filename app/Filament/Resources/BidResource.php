<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BidResource\Pages;
use App\Models\Bid;
use App\Models\Procurement;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BidResource extends Resource
{
    protected static ?string $model = Bid::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    public static function getModelLabel(): string
    {
        return 'Offre';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Offres';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏗️ Marchés Publics';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Marché et Fournisseur')
                    ->schema([
                        Forms\Components\Select::make('procurement_id')
                            ->label('Marché Public')
                            ->options(Procurement::whereIn('status', ['published', 'bids_received'])
                                ->orderBy('reference', 'desc')
                                ->get()
                                ->pluck('reference', 'id'))
                            ->required()
                            ->reactive()
                            ->searchable()
                            ->native(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $count = Bid::where('procurement_id', $state)->count() + 1;
                                    $procurement = Procurement::find($state);
                                    $set('reference', $procurement->reference . '/OFFRE-' . str_pad($count, 2, '0', STR_PAD_LEFT));
                                }
                            }),

                        Forms\Components\Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->options(Supplier::where('status', 'active')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Raison Sociale')
                                    ->required(),
                                Forms\Components\TextInput::make('registration_number')
                                    ->label('N° RC')
                                    ->required(),
                            ]),

                        Forms\Components\TextInput::make('reference')
                            ->label('Référence de l\'Offre')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Montants')
                    ->schema([
                        Forms\Components\TextInput::make('bid_amount')
                            ->label('Montant de l\'Offre (HT)')
                            ->required()
                            ->numeric()
                            ->prefix('FCFA')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $ht = $state ?? 0;
                                $vat = $ht * 0.1925; // TVA 19.25%
                                $ttc = $ht + $vat;

                                $set('bid_amount_ht', $ht);
                                $set('vat_amount', round($vat, 2));
                                $set('bid_amount_ttc', round($ttc, 2));
                            }),

                        Forms\Components\TextInput::make('vat_amount')
                            ->label('Montant TVA (19.25%)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('bid_amount_ttc')
                            ->label('Montant TTC')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Délais')
                    ->schema([
                        Forms\Components\TextInput::make('execution_period')
                            ->label('Délai d\'Exécution')
                            ->numeric()
                            ->suffix('jours')
                            ->helperText('Délai proposé pour l\'exécution du marché'),

                        Forms\Components\TextInput::make('warranty_period')
                            ->label('Période de Garantie')
                            ->numeric()
                            ->suffix('mois')
                            ->helperText('Durée de la garantie proposée'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Soumission')
                    ->schema([
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Date/Heure de Soumission')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        Forms\Components\TextInput::make('submitted_by')
                            ->label('Soumis par')
                            ->maxLength(255)
                            ->helperText('Nom du représentant du fournisseur'),

                        Forms\Components\Toggle::make('is_late')
                            ->label('Hors Délai')
                            ->helperText('Marquer si l\'offre a été soumise après la date limite'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Conformité')
                    ->schema([
                        Forms\Components\Toggle::make('is_technically_compliant')
                            ->label('Conforme Techniquement')
                            ->reactive(),

                        Forms\Components\Textarea::make('technical_compliance_notes')
                            ->label('Notes Conformité Technique')
                            ->rows(2)
                            ->visible(fn(callable $get) => $get('is_technically_compliant') === false),

                        Forms\Components\Toggle::make('is_financially_compliant')
                            ->label('Conforme Financièrement')
                            ->reactive(),

                        Forms\Components\Textarea::make('financial_compliance_notes')
                            ->label('Notes Conformité Financière')
                            ->rows(2)
                            ->visible(fn(callable $get) => $get('is_financially_compliant') === false),

                        Forms\Components\Toggle::make('has_required_documents')
                            ->label('Documents Complets')
                            ->reactive(),

                        Forms\Components\Textarea::make('missing_documents')
                            ->label('Documents Manquants')
                            ->rows(2)
                            ->visible(fn(callable $get) => $get('has_required_documents') === false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Évaluation')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'submitted' => 'Soumise',
                                'under_review' => 'En Examen',
                                'compliant' => 'Conforme',
                                'non_compliant' => 'Non Conforme',
                                'shortlisted' => 'Présélectionnée',
                                'rejected' => 'Rejetée',
                                'awarded' => 'Retenue',
                                'not_awarded' => 'Non Retenue',
                            ])
                            ->default('submitted')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('total_score')
                            ->label('Score Total')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/100')
                            ->helperText('Score moyen des évaluations'),

                        Forms\Components\TextInput::make('rank')
                            ->label('Classement')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Position dans le classement final'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes et Observations')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('procurement.reference')
                    ->label('Marché')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('bid_amount_ttc')
                    ->label('Montant TTC')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('Retard')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'submitted',
                        'info' => 'under_review',
                        'success' => fn($state) => in_array($state, ['compliant', 'shortlisted', 'awarded']),
                        'danger' => fn($state) => in_array($state, ['non_compliant', 'rejected', 'not_awarded']),
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'submitted' => 'Soumise',
                        'under_review' => 'En Examen',
                        'compliant' => 'Conforme',
                        'non_compliant' => 'Non Conforme',
                        'shortlisted' => 'Présélectionnée',
                        'rejected' => 'Rejetée',
                        'awarded' => 'Retenue',
                        'not_awarded' => 'Non Retenue',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Score')
                    ->suffix('/100')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rank')
                    ->label('Rang')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state == 1 => 'success',
                        $state == 2 => 'warning',
                        $state == 3 => 'info',
                        default => 'secondary',
                    })
                    ->toggleable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('procurement_id')
                    ->label('Marché')
                    ->options(Procurement::orderBy('reference', 'desc')->pluck('reference', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Fournisseur')
                    ->options(Supplier::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'submitted' => 'Soumise',
                        'under_review' => 'En Examen',
                        'compliant' => 'Conforme',
                        'non_compliant' => 'Non Conforme',
                        'shortlisted' => 'Présélectionnée',
                        'rejected' => 'Rejetée',
                        'awarded' => 'Retenue',
                        'not_awarded' => 'Non Retenue',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('Hors Délai')
                    ->placeholder('Tous')
                    ->trueLabel('En retard')
                    ->falseLabel('Dans les délais'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),

                Tables\Actions\Action::make('evaluate')
                    ->label('Évaluer')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(fn($record) => in_array($record->status, ['submitted', 'under_review', 'compliant']))
                    ->url(fn($record) => route('filament.admin.resources.bids.evaluate', $record)),
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
            'index' => Pages\ListBids::route('/'),
            'create' => Pages\CreateBid::route('/create'),
            'edit' => Pages\EditBid::route('/{record}/edit'),
        ];
    }
}
