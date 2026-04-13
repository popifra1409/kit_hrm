<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
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

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'medical' => 'Service Médical',
                                'administrative' => 'Service Administratif',
                            ])
                            ->required()
                            ->reactive()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rattachement')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Département Administratif')
                            ->options(function () {
                                return \App\Models\Department::where('type', 'administrative')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->visible(fn($get) => $get('type') === 'administrative'),

                        Forms\Components\Select::make('medical_department_id')
                            ->label('Département Médical')
                            ->options(function () {
                                return \App\Models\MedicalDepartment::pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->visible(fn($get) => $get('type') === 'medical'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Responsables - Service Médical')
                    ->schema([
                        Forms\Components\Select::make('head_of_service_id')
                            ->label('Chef de Service')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('major_id')
                            ->label('Major')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->visible(fn($get) => $get('type') === 'medical'),

                Forms\Components\Section::make('Responsables - Service Administratif')
                    ->schema([
                        Forms\Components\Select::make('service_chief_id')
                            ->label('Chef de Service')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('deputy_director_id')
                            ->label('Sous-Directeur')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->visible(fn($get) => $get('type') === 'administrative'),
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

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'medical',
                        'warning' => 'administrative',
                    ])
                    ->formatStateUsing(fn(string $state): string => $state === 'medical' ? 'Médical' : 'Administratif'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'medical' => 'Médical',
                        'administrative' => 'Administratif',
                    ]),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-briefcase';
    }

    public static function getNavigationLabel(): string
    {
        return 'Services';
    }
    public static function getModelLabel(): string
    {
        return 'Service';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Services';
    }
}
