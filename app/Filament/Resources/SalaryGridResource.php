<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryGridResource\Pages;
use App\Filament\Resources\SalaryGridResource\RelationManagers;
use App\Models\SalaryGrid;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalaryGridResource extends Resource
{
    protected static ?string $model = SalaryGrid::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Catégorie et Échelon')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options(array_combine(range(3, 12), range(3, 12)))
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('echelon')
                            ->label('Échelon')
                            ->options(array_combine(range(1, 12), range(1, 12)))
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('base_salary')
                            ->label('Salaire de Base')
                            ->required()
                            ->numeric()
                            ->prefix('FCFA')
                            ->step(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Période d\'Application')
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
                            ->helperText('Laisser vide si toujours en vigueur'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('echelon')
                    ->label('Échelon')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Salaire de Base')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date Application')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Date Fin')
                    ->date('d/m/Y')
                    ->placeholder('En vigueur')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('category', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(array_combine(range(3, 12), range(3, 12))),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
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
            'index' => Pages\ListSalaryGrids::route('/'),
            'create' => Pages\CreateSalaryGrid::route('/create'),
            'edit' => Pages\EditSalaryGrid::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Grille Salariale';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Grille Salariale';
    }

    public static function getNavigationGroup(): ?string
    {
        return '💰 Gestion de la Paie';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-table-cells';
    }

    public static function getNavigationLabel(): string
    {
        return 'Grille Salariale';
    }
}
