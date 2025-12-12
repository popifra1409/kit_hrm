<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeHierarchyResource\Pages;
use App\Filament\Resources\EmployeeHierarchyResource\RelationManagers;
use App\Models\EmployeeHierarchy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeHierarchyResource extends Resource
{
    protected static ?string $model = EmployeeHierarchy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employé et Niveau')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('organization_level_id')
                            ->label('Niveau hiérarchique')
                            ->relationship('organizationLevel', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('superior_id')
                            ->label('Supérieur hiérarchique')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Rattachement')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Département')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('medical_department_id')
                            ->label('Département Médical')
                            ->relationship('medicalDepartment', 'name')
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Période et Décision')
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

                        Forms\Components\TextInput::make('appointment_decision')
                            ->label('N° Décision de nomination')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_current')
                            ->label('Position actuelle')
                            ->default(true),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('organizationLevel.name')
                    ->label('Niveau hiérarchique')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('superior.full_name')
                    ->label('Supérieur')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('Actuelle')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_current')
                    ->label('Positions actuelles seulement')
                    ->query(fn($query) => $query->where('is_current', true)),
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
            'index' => Pages\ListEmployeeHierarchies::route('/'),
            'create' => Pages\CreateEmployeeHierarchy::route('/create'),
            'edit' => Pages\EditEmployeeHierarchy::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getNavigationLabel(): string
    {
        return 'Hiérarchie';
    }
    public static function getModelLabel(): string
    {
        return 'Hiérarchie Employé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Hiérarchies Employés';
    }
}
