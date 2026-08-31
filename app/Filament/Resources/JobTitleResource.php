<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobTitleResource\Pages;
use App\Models\JobTitle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class JobTitleResource extends Resource
{
    protected static ?string $model = JobTitle::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Titre du Poste')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ex: DG, CHEF-SERV'),

                        Forms\Components\Select::make('level')
                            ->label('Niveau Hiérarchique')
                            ->options([
                                'president' => '👑 Président du Conseil',
                                'director_general' => '📊 Directeur Général',
                                'director_general_adjoint' => '📊 Directeur Général Adjoint',
                                'director' => '📋 Directeur',
                                'chief_department' => '🏢 Chef de Département/Sous-Direction',
                                'chief_service' => '🏥 Chef de Service',
                                'major' => '🎖️ Major',
                                'chief_unit' => '⚙️ Chef d\'Unité',
                                'employee' => '👤 Employé',
                            ])
                            ->required()
                            ->native(false)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('hierarchy_level')
                            ->label('Niveau Hiérarchique (Chiffre)')
                            ->numeric()
                            ->required()
                            ->helperText('0 = Président, 10 = Employé standard'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('is_managerial')
                            ->label('Poste Managérial')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('level')
                    ->label('Niveau')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'president' => '👑 Président',
                        'director_general' => '📊 DG',
                        'director_general_adjoint' => '📊 DGA',
                        'director' => '📋 Directeur',
                        'chief_department' => '🏢 Chef Dept',
                        'chief_service' => '🏥 Chef Serv',
                        'major' => '🎖️ Major',
                        'chief_unit' => '⚙️ Chef Unité',
                        'employee' => '👤 Employé',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('hierarchy_level')
                    ->label('Rang')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_managerial')
                    ->label('Managérial')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employés')
                    ->counts('employees')
                    ->badge()
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_managerial')
                    ->label('Poste Managérial'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Voir'),
                    Tables\Actions\EditAction::make()->label('Modifier'),
                    Tables\Actions\DeleteAction::make()->label('Supprimer'),
                ])
                    ->button()
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal'),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobTitles::route('/'),
            'create' => Pages\CreateJobTitle::route('/create'),
            'edit' => Pages\EditJobTitle::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Titre de Poste';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Titres de Postes';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getNavigationLabel(): string
    {
        return 'Titres de Postes';
    }
}
