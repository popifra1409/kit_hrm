<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeAffectationResource\Pages;
use App\Filament\Resources\EmployeeAffectationResource\RelationManagers;
use App\Models\EmployeeAffectation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeAffectationResource extends Resource
{
    protected static ?string $model = EmployeeAffectation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employé et Service')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('position_id')
                            ->label('Poste')
                            ->relationship('position', 'name')
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Période')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->nullable()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Toggle::make('is_current')
                            ->label('Affectation actuelle')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Détails de l\'affectation')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de l\'affectation')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('decision_number')
                            ->label('N° de décision')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('position.name')
                    ->label('Poste')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Date de début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('En cours'),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('Actuelle')
                    ->boolean(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('is_current')
                    ->label('Affectations actuelles seulement')
                    ->query(fn($query) => $query->where('is_current', true)),

                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name'),
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
            'index' => Pages\ListEmployeeAffectations::route('/'),
            'create' => Pages\CreateEmployeeAffectation::route('/create'),
            'edit' => Pages\EditEmployeeAffectation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '📋 Contrats & Affectations';
    }

    public static function getNavigationSort(): ?int
    {
        return 9;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrows-right-left';
    }

    public static function getNavigationLabel(): string
    {
        return 'Affectations';
    }
    public static function getModelLabel(): string
    {
        return 'Affectation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Affectations';
    }
}
