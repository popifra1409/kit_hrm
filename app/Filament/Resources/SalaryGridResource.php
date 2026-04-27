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
                Forms\Components\Section::make('Catégorie et Échelon')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Catégorie')
                                    ->options(array_combine(range(1, 12), range(1, 12)))
                                    ->required()
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\Select::make('echelon')
                                    ->label('Échelon')
                                    ->options(array_combine(range(1, 12), range(1, 12)))
                                    ->required()
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\TextInput::make('base_salary')
                                    ->label('Salaire de Base')
                                    ->required()
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->step(1000)
                                    ->helperText('Montant en FCFA'),
                            ]),
                    ])
                    ->icon('heroicon-o-currency-dollar'),

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
                fn(Builder $query) =>
                $query->orderBy('category', 'asc')
                    ->orderBy('echelon', 'asc')
            )
            ->columns([
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
                    ->getStateUsing(
                        fn(SalaryGrid $record) =>
                        'C' . $record->category . 'E' . $record->echelon
                    )
                    ->badge()
                    ->color('info')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            if (preg_match('/C?(\d+)[_\-\s]*E?(\d+)/i', $search, $matches)) {
                                $q->where('category', $matches[1])
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
                // ... tous vos filtres restent identiques
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(array_combine(range(1, 12), range(1, 12)))
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('echelon')
                    ->label('Échelon')
                    ->options(array_combine(range(1, 12), range(1, 12)))
                    ->multiple()
                    ->searchable(),

                Filter::make('salary_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_salary')
                                    ->label('Salaire minimum')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->placeholder('Ex: 100000'),

                                Forms\Components\TextInput::make('max_salary')
                                    ->label('Salaire maximum')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->placeholder('Ex: 500000'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_salary'],
                                fn(Builder $query, $min): Builder => $query->where('base_salary', '>=', $min),
                            )
                            ->when(
                                $data['max_salary'],
                                fn(Builder $query, $max): Builder => $query->where('base_salary', '<=', $max),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_salary'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Salaire min: ' . number_format($data['min_salary'], 0, ',', ' ') . ' FCFA')
                                ->removeField('min_salary');
                        }
                        if ($data['max_salary'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Salaire max: ' . number_format($data['max_salary'], 0, ',', ' ') . ' FCFA')
                                ->removeField('max_salary');
                        }
                        return $indicators;
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Inactifs uniquement'),

                Tables\Filters\TernaryFilter::make('in_effect')
                    ->label('En Vigueur')
                    ->placeholder('Tous')
                    ->trueLabel('En vigueur')
                    ->falseLabel('Archivés')
                    ->queries(
                        true: fn(Builder $query): Builder => $query->whereNull('end_date')->orWhere('end_date', '>', now()),
                        false: fn(Builder $query): Builder => $query->whereNotNull('end_date')->where('end_date', '<=', now()),
                    ),
            ])
            ->actions([
                // ... vos actions restent identiques
            ])
            ->bulkActions([
                // ... vos bulk actions restent identiques
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
