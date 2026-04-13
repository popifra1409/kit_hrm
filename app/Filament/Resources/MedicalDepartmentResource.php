<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicalDepartmentResource\Pages;
use App\Filament\Resources\MedicalDepartmentResource\RelationManagers;
use App\Models\MedicalDepartment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MedicalDepartmentResource extends Resource
{
    protected static ?string $model = MedicalDepartment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Select::make('head_of_department_id')
                    ->label('Chef de Département')
                    ->options(function () {
                        return \App\Models\Employee::where('personnel_type', 'soignant')
                            ->get()
                            ->pluck('full_name', 'id');
                    })
                    ->searchable()
                    ->nullable(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),

                Tables\Columns\TextColumn::make('headOfDepartment.full_name')
                    ->label('Chef de Département')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                //
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
            'index' => Pages\ListMedicalDepartments::route('/'),
            'create' => Pages\CreateMedicalDepartment::route('/create'),
            'edit' => Pages\EditMedicalDepartment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationLabel(): string
    {
        return 'Départements Médicaux';
    }
    public static function getModelLabel(): string
    {
        return 'Département Médical';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Départements Médicaux';
    }
}
