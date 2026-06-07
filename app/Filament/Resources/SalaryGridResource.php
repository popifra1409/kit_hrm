<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryGridResource\Pages;
use App\Models\SalaryGrid;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
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
                                    ->label('Catégorie')
                                    ->options(\App\Enums\EmployeeClassification::getCategoryOptions())
                                    ->searchable()
                                    ->required(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                    ->native(false),

                                Forms\Components\Select::make('echelon')
                                    ->label('Échelon')
                                    ->options(\App\Enums\EmployeeClassification::getEchelonOptions())
                                    ->searchable()
                                    ->required(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                    ->native(false),

                                Forms\Components\Placeholder::make('classification_display')
                                    ->label('📋 Classification')
                                    ->content(function (Forms\Get $get) {
                                        $category = $get('category');
                                        $echelon = $get('echelon');

                                        if ($category && $echelon) {
                                            $classification = "{$category}{$echelon}";
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="p-2 bg-blue-100 text-blue-900 rounded font-bold">' . $classification . '</div>'
                                            );
                                        }
                                        return 'Sélectionnez catégorie et échelon';
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
                                                '<div class="p-2 bg-purple-100 text-purple-900 rounded font-bold">Cat. ' . $category . ' / Éch. ' . $echelon . '</div>'
                                            );
                                        }
                                        return 'Sélectionnez catégorie et échelon';
                                    }),
                            ]),
                    ])
                    ->icon('heroicon-o-currency-dollar'),

                Forms\Components\Section::make('Salaire de Base')
                    ->schema([
                        Forms\Components\TextInput::make('base_salary')
                            ->label('Salaire de Base')
                            ->required()
                            ->numeric()
                            ->prefix('FCFA')
                            ->step(1000)
                            ->helperText('Montant en FCFA')
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
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->orderBy('classification_type', 'asc')
                    ->orderBy('category', 'asc')
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
                    ->getStateUsing(function (SalaryGrid $record) {
                        if ($record->classification_type === 'cameroon') {
                            return "{$record->category}{$record->echelon}";
                        }
                        return "C{$record->category}E{$record->echelon}";
                    })
                    ->badge()
                    ->color('info')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            // Recherche par nomenclature camerounaise (A1, B2, etc.)
                            if (preg_match('/^([A-E])([1-8])$/i', $search, $matches)) {
                                $q->where('classification_type', 'cameroon')
                                    ->where('category', strtoupper($matches[1]))
                                    ->where('echelon', $matches[2]);
                            }
                            // Recherche par numérique (C1E2, etc.)
                            elseif (preg_match('/C?(\d+)[_\-\s]*E?(\d+)/i', $search, $matches)) {
                                $q->where('classification_type', 'numeric')
                                    ->where('category', $matches[1])
                                    ->where('echelon', $matches[2]);
                            }
                        });
                    }),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Salaire de Base')
                    ->money('XAF')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success')
                    ->description(
                        fn(SalaryGrid $record) =>
                        'Soit ' . number_format($record->base_salary / 1000, 0, ',', ' ') . 'K FCFA'
                    ),

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
                // ✅ FILTRE SIMPLE PAR TYPE
                Tables\Filters\SelectFilter::make('classification_type')
                    ->label('Type de Classification')
                    ->options([
                        'cameroon' => '🇨🇲 Nomenclature Camerounaise',
                        'numeric' => '🔢 Classification Numérique',
                    ]),

                // ✅ FILTRE SIMPLE PAR CATÉGORIE (tous les types)
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(function () {
                        $cameroon = \App\Enums\EmployeeClassification::getCategoryOptions();
                        $numeric = array_combine(range(1, 12), range(1, 12));
                        return array_merge($cameroon, $numeric);
                    })
                    ->searchable(),

                // ✅ FILTRE SIMPLE PAR ÉCHELON
                Tables\Filters\SelectFilter::make('echelon')
                    ->label('Échelon')
                    ->options(array_combine(range(1, 12), range(1, 12)))
                    ->searchable(),

                // ✅ FILTRE STATUT
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
