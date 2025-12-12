<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('matricule')
                            ->label('Matricule')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('first_name')
                            ->label('Prénom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Date de naissance')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()->subYears(18)),

                        Forms\Components\Select::make('marital_status')
                            ->label('État civil')
                            ->options([
                                'single' => 'Célibataire',
                                'married' => 'Marié(e)',
                                'divorced' => 'Divorcé(e)',
                                'widowed' => 'Veuf/Veuve',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('children_under_6')
                            ->label('Enfants de moins de 6 ans')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('total_children')
                            ->label('Nombre total d\'enfants')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informations Professionnelles')
                    ->schema([
                        Forms\Components\TextInput::make('qualification')
                            ->label('Qualification / Poste')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('personnel_type')
                            ->label('Type de personnel')
                            ->options([
                                'soignant' => 'Personnel Soignant',
                                'non_soignant' => 'Personnel Non-Soignant',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('department_id')
                            ->label('Département')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du département')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('current_service_id')
                            ->label('Service actuel')
                            ->relationship('currentService', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('position_id')
                            ->label('Poste')
                            ->relationship('position', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du poste')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('contract_type_id')
                            ->label('Type de contrat')
                            ->relationship('contractType', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Catégorie et Échelon')
                    ->schema([
                        Forms\Components\TextInput::make('category_recruitment')
                            ->label('Catégorie/Échelon au recrutement')
                            ->placeholder('Ex: 7/1')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('category_current')
                            ->label('Catégorie/Échelon actuelle')
                            ->placeholder('Ex: 7/9')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('category_number')
                            ->label('Numéro de catégorie')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12),

                        Forms\Components\TextInput::make('echelon_number')
                            ->label('Numéro d\'échelon')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(15),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Dates Importantes')
                    ->schema([
                        Forms\Components\DatePicker::make('recruitment_date')
                            ->label('Date de recrutement')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('service_start_date')
                            ->label('Date de prise de service')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('retirement_age')
                            ->label('Âge de départ à la retraite')
                            ->numeric()
                            ->default(60)
                            ->minValue(55)
                            ->maxValue(70)
                            ->suffix('ans')
                            ->helperText('La date de retraite sera calculée automatiquement'),

                        Forms\Components\DatePicker::make('retirement_date')
                            ->label('Date de retraite')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Informations Bancaires')
                    ->schema([
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Numéro de compte bancaire')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nom de la banque')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contract_number')
                            ->label('N° de contrat/décision')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Coordonnées')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(2)
                            ->maxLength(65535),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dossier Disciplinaire')
                    ->schema([
                        Forms\Components\TextInput::make('disciplinary_points')
                            ->label('Points disciplinaires')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('0 = Aucune sanction'),

                        Forms\Components\Textarea::make('disciplinary_notes')
                            ->label('Notes disciplinaires')
                            ->rows(3)
                            ->maxLength(65535),
                    ])
                    ->columns(1)
                    ->collapsed(),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'active' => 'Actif',
                                'on_leave' => 'En congé',
                                'retired' => 'Retraité',
                                'suspended' => 'Suspendu',
                                'terminated' => 'Résilié',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),

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
                Tables\Columns\TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('qualification')
                    ->label('Qualification')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('currentService.name')
                    ->label('Service')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category_current')
                    ->label('Catégorie/Échelon')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('personnel_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'success' => 'soignant',
                        'warning' => 'non_soignant',
                    ])
                    ->formatStateUsing(fn(string $state): string => $state === 'soignant' ? 'Soignant' : 'Non-Soignant'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'on_leave',
                        'danger' => ['suspended', 'terminated'],
                        'secondary' => 'retired',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Actif',
                        'on_leave' => 'En congé',
                        'retired' => 'Retraité',
                        'suspended' => 'Suspendu',
                        'terminated' => 'Résilié',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('recruitment_date')
                    ->label('Date recrutement')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('retirement_date')
                    ->label('Date retraite')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('personnel_type')
                    ->label('Type de personnel')
                    ->options([
                        'soignant' => 'Personnel Soignant',
                        'non_soignant' => 'Personnel Non-Soignant',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'on_leave' => 'En congé',
                        'retired' => 'Retraité',
                        'suspended' => 'Suspendu',
                        'terminated' => 'Résilié',
                    ]),

                Tables\Filters\SelectFilter::make('current_service_id')
                    ->label('Service')
                    ->relationship('currentService', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Employé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Employés';
    }

    // Après la classe, ajoutez ces méthodes statiques

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return 'Employés';
    }
}
