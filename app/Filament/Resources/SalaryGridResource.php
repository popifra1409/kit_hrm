<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryGridResource\Pages;
use App\Models\SalaryGrid;
use App\Support\CameroonCivilServiceGrid;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalaryGridResource extends Resource
{
    protected static ?string $model = SalaryGrid::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = '💰 Gestion de la Paie';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Grille Salariale';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Grille Salariale';
    }

    /**
     * Fonctionnaires : remplit indice + composantes + salaire de base depuis la grille officielle.
     */
    protected static function fillFromOfficialGrid(Forms\Get $get, Forms\Set $set): void
    {
        $row = CameroonCivilServiceGrid::find((string) $get('category'), (string) $get('echelon'));

        if (!$row) {
            return;
        }

        $set('indice', $row['indice']);
        $set('salaire_indiciaire_brut', $row['salaire_indiciaire_brut']);
        $set('complement_forfaitaire', $row['complement_forfaitaire']);
        $set('indemnite_logement', $row['indemnite_logement']);
        $set('base_salary', $row['salaire_indiciaire_brut']);
    }

    /**
     * Contractuels : l'indice est retrouvé par correspondance de salaire dans la grille
     * officielle des fonctionnaires (indice au salaire indiciaire brut le plus proche).
     */
    protected static function syncNumericIndiceFromSalary($state, Forms\Set $set): void
    {
        if ($state === null || $state === '') {
            return;
        }

        $set('indice', CameroonCivilServiceGrid::nearestIndiceForSalary((float) $state));
    }

    protected static function resetDerivedFields(Forms\Set $set): void
    {
        $set('indice', null);
        $set('salaire_indiciaire_brut', null);
        $set('complement_forfaitaire', null);
        $set('indemnite_logement', null);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Type de Classification')
                    ->schema([
                        Forms\Components\Radio::make('classification_type')
                            ->label('Type de Classification')
                            ->options([
                                'cameroon' => '🇨🇲 Nomenclature Camerounaise (Fonctionnaires)',
                                'numeric' => '🔢 Classification Numérique (Contractuels)',
                            ])
                            ->default('numeric')
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('category', null);
                                $set('echelon', null);
                                static::resetDerivedFields($set);
                            })
                            ->inline()
                            ->required(),
                    ])
                    ->icon('heroicon-o-tag')
                    ->collapsed(),

                Forms\Components\Section::make('Catégorie et Échelon')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->visible(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Classe / Catégorie')
                                    ->options(CameroonCivilServiceGrid::categoryOptions())
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('echelon', null);
                                        static::resetDerivedFields($set);
                                    })
                                    ->required(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                    ->native(false),

                                Forms\Components\Select::make('echelon')
                                    ->label('Échelon')
                                    ->options(fn(Forms\Get $get) => CameroonCivilServiceGrid::echelonOptions($get('category')))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => static::fillFromOfficialGrid($get, $set))
                                    ->required(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                    ->native(false),

                                Forms\Components\Placeholder::make('classification_display')
                                    ->label('📋 Classification')
                                    ->content(function (Forms\Get $get) {
                                        $category = $get('category');
                                        $echelon = $get('echelon');

                                        if ($category && $echelon) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="p-2 bg-blue-100 text-blue-900 rounded font-bold">' . e($category) . ' · ' . e($echelon) . '</div>'
                                            );
                                        }
                                        return 'Sélectionnez classe et échelon';
                                    }),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->visible(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Catégorie')
                                    ->options(array_combine(range(1, 12), range(1, 12)))
                                    ->required(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                                    ->native(false)
                                    ->searchable()
                                    ->suffix('/ 12'),

                                Forms\Components\Select::make('echelon')
                                    ->label('Échelon')
                                    ->options(array_combine(range(1, 12), range(1, 12)))
                                    ->required(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                                    ->native(false)
                                    ->searchable()
                                    ->suffix('/ 12'),

                                Forms\Components\Placeholder::make('classification_numeric_display')
                                    ->label('📋 Classification')
                                    ->content(function (Forms\Get $get) {
                                        $category = $get('category');
                                        $echelon = $get('echelon');

                                        if ($category && $echelon) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="p-2 bg-purple-100 text-purple-900 rounded font-bold">Cat. ' . e($category) . ' / Éch. ' . e($echelon) . '</div>'
                                            );
                                        }
                                        return 'Sélectionnez catégorie et échelon';
                                    }),
                            ]),
                    ])
                    ->icon('heroicon-o-currency-dollar'),

                Forms\Components\Section::make('Indice')
                    ->schema([
                        Forms\Components\TextInput::make('indice')
                            ->label('Indice')
                            ->numeric()
                            ->minValue(0)
                            ->readOnly(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                            ->dehydrated()
                            ->helperText(fn(Forms\Get $get) => $get('classification_type') === 'numeric'
                                ? "Calculé automatiquement : indice de la grille officielle des fonctionnaires dont le salaire indiciaire brut est le plus proche du salaire de base saisi ci-dessous."
                                : 'Repris de la grille officielle du 02/02/2024 — modifiable en cas de révision de la grille'),

                        Forms\Components\Grid::make(3)
                            ->visible(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                            ->schema([
                                Forms\Components\TextInput::make('salaire_indiciaire_brut')
                                    ->label('Salaire indiciaire brut (1)')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->step(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn($state, Forms\Set $set) => $set('base_salary', $state)),

                                Forms\Components\TextInput::make('complement_forfaitaire')
                                    ->label('Complément forfaitaire (2)')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->step(1)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('indemnite_logement')
                                    ->label('Indemnité de logement (3)')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->step(1)
                                    ->live(onBlur: true),
                            ]),

                        Forms\Components\Placeholder::make('total_display')
                            ->label('Total brut (1) + (2) + (3)')
                            ->visible(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                            ->content(function (Forms\Get $get) {
                                $total = (float) $get('salaire_indiciaire_brut')
                                    + (float) $get('complement_forfaitaire')
                                    + (float) $get('indemnite_logement');

                                return number_format($total, 0, ',', ' ') . ' FCFA';
                            }),
                    ])
                    ->icon('heroicon-o-hashtag'),

                Forms\Components\Section::make('Salaire de Base')
                    ->schema([
                        Forms\Components\TextInput::make('base_salary')
                            ->label('Salaire de Base')
                            ->required(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                            ->numeric()
                            ->prefix('FCFA')
                            ->step(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                if ($get('classification_type') === 'numeric') {
                                    static::syncNumericIndiceFromSalary($state, $set);
                                }
                            })
                            ->helperText(fn(Forms\Get $get) => $get('classification_type') === 'cameroon'
                                ? 'Facultatif — repris automatiquement du salaire indiciaire brut de la grille officielle.'
                                : "Montant en FCFA — l'indice ci-dessus se met à jour automatiquement à la saisie.")
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-banknotes'),

                Forms\Components\Section::make('Période d\'Application')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('effective_date')
                                    ->label('Date d\'Application')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Date de Fin')
                                    ->nullable()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Laisser vide si toujours en vigueur')
                                    ->after('effective_date'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Actif')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ])
                    ->icon('heroicon-o-calendar')
                    ->collapsible(),

                Forms\Components\Section::make('Notes et Commentaires')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Remarques, historique des modifications...')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->orderBy('classification_type', 'asc')
                    ->orderByRaw("CASE WHEN classification_type = 'cameroon' THEN category END ASC")
                    ->orderBy('indice', 'asc')
                    ->orderBy('echelon', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('classification_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => $state === 'cameroon' ? '🇨🇲 Cameroun' : '🔢 Numérique')
                    ->badge()
                    ->color(fn($state) => $state === 'cameroon' ? 'info' : 'warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->size('lg')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('echelon')
                    ->label('Échelon')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('success')
                    ->size('lg')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('indice')
                    ->label('Indice')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->placeholder('—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return is_numeric($search) ? $query->where('indice', (int) $search) : $query;
                    }),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Salaire de Base')
                    ->money('XAF')
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success')
                    ->placeholder('—')
                    ->description(
                        fn(SalaryGrid $record) =>
                        'Soit ' . number_format(((float) $record->base_salary) / 1000, 0, ',', ' ') . 'K FCFA'
                    ),

                Tables\Columns\TextColumn::make('complement_forfaitaire')
                    ->label('Compl. forfaitaire')
                    ->money('XAF')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('indemnite_logement')
                    ->label('Indemnité logement')
                    ->money('XAF')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_salary')
                    ->label('Total brut')
                    ->getStateUsing(fn(SalaryGrid $record) => $record->classification_type === 'cameroon' ? $record->total_salary : null)
                    ->money('XAF')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date Application')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Date Fin')
                    ->date('d/m/Y')
                    ->placeholder('En vigueur')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classification_type')
                    ->label('Type de Classification')
                    ->options([
                        'cameroon' => '🇨🇲 Nomenclature Camerounaise',
                        'numeric' => '🔢 Classification Numérique',
                    ]),

                // L'opérateur + préserve les clés (array_merge les renuméroterait : "1" deviendrait "0").
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(function () {
                        return CameroonCivilServiceGrid::categoryOptions()
                            + array_combine(range(1, 12), range(1, 12));
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('echelon')
                    ->label('Échelon')
                    ->options(function () {
                        return CameroonCivilServiceGrid::allEchelonOptions()
                            + array_combine(range(1, 12), range(1, 12));
                    })
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Inactifs uniquement'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Créer une grille salariale')
                    ->icon('heroicon-o-plus'),
            ])
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaryGrids::route('/'),
            'create' => Pages\CreateSalaryGrid::route('/create'),
            'edit' => Pages\EditSalaryGrid::route('/{record}/edit'),
            'matrix' => Pages\MatrixView::route('/matrix'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
