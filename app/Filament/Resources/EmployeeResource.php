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
                Forms\Components\Wizard::make([
                    // ÉTAPE 1 : ÉTAT CIVIL
                    Forms\Components\Wizard\Step::make('État Civil')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Section::make('Identité')
                                ->schema([
                                    Forms\Components\TextInput::make('matricule')
                                        ->label('Matricule')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255)
                                        ->placeholder('Ex: EMP2025001'),

                                    Forms\Components\TextInput::make('last_name')
                                        ->label('Nom de famille')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('first_name')
                                        ->label('Prénom(s)')
                                        ->maxLength(255)
                                        ->helperText('Optionnel'),

                                    Forms\Components\Select::make('gender')
                                        ->label('Sexe')
                                        ->options([
                                            'M' => 'Masculin',
                                            'F' => 'Féminin',
                                        ])
                                        ->required()
                                        ->native(false),

                                    Forms\Components\DatePicker::make('birth_date')
                                        ->label('Date de Naissance')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->maxDate(now()->subYears(18))
                                        ->helperText('Minimum 18 ans'),

                                    Forms\Components\TextInput::make('birth_place')
                                        ->label('Lieu de Naissance')
                                        ->maxLength(255),

                                    Forms\Components\Select::make('marital_status')
                                        ->label('État Civil')
                                        ->options([
                                            'single' => 'Célibataire',
                                            'married' => 'Marié(e)',
                                            'divorced' => 'Divorcé(e)',
                                            'widowed' => 'Veuf/Veuve',
                                        ])
                                        ->required()
                                        ->native(false),

                                    Forms\Components\FileUpload::make('photo')
                                        ->label('Photo')
                                        ->image()
                                        ->directory('employees/photos')
                                        ->maxSize(2048)
                                        ->imageEditor()
                                        ->imageEditorAspectRatios([
                                            '1:1',
                                        ])
                                        ->helperText('Photo d\'identité (max 2MB)'),
                                ])
                                ->columns(2),
                        ]),

                    // ÉTAPE 2 : COORDONNÉES
                    Forms\Components\Wizard\Step::make('Coordonnées')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\Section::make('Contact')
                                ->schema([
                                    Forms\Components\TextInput::make('phone')
                                        ->label('Téléphone')
                                        ->tel()
                                        // ->required()
                                        ->maxLength(255)
                                        ->placeholder('+237 6XX XXX XXX'),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(255),

                                    Forms\Components\Textarea::make('address')
                                        ->label('Adresse Complète')
                                        ->rows(2)
                                        ->maxLength(65535)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('city')
                                        ->label('Ville')
                                        ->maxLength(255)
                                        ->default('Yaoundé'),
                                ])
                                ->columns(2),
                        ]),

                    // ÉTAPE 3 : INFORMATIONS PROFESSIONNELLES
                    Forms\Components\Wizard\Step::make('Informations Professionnelles')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            Forms\Components\Section::make('Poste et Affectation')
                                ->schema([
                                    Forms\Components\TextInput::make('qualification')
                                        ->label('Titre/Qualification Professionnelle')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Ex: Médecin Généraliste, Infirmier(ère), Comptable')
                                        ->helperText('Titre professionnel ou diplôme')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('personnel_type')
                                        ->label('Type de Personnel')
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
                                            Forms\Components\Select::make('type')
                                                ->label('Type')
                                                ->options([
                                                    'medical' => 'Médical',
                                                    'administrative' => 'Administratif',
                                                ])
                                                ->required()
                                                ->native(false),
                                            Forms\Components\TextInput::make('level')
                                                ->label('Niveau hiérarchique')
                                                ->numeric()
                                                ->default(1),
                                        ]),

                                    Forms\Components\Select::make('current_service_id')
                                        ->label('Service Actuel')
                                        ->relationship('currentService', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Nom du service')
                                                ->required(),
                                            Forms\Components\TextInput::make('code')
                                                ->label('Code')
                                                ->required(),
                                            Forms\Components\Select::make('type')
                                                ->label('Type de service')
                                                ->options([
                                                    'medical' => 'Service Médical',
                                                    'administrative' => 'Service Administratif',
                                                ])
                                                ->required()
                                                ->reactive()
                                                ->native(false),
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
                                        ]),

                                    Forms\Components\Select::make('position_id')
                                        ->label('Fonction dans l\'Organigramme')
                                        ->relationship('position', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->helperText('Poste officiel dans la structure')
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Nom de la fonction')
                                                ->required(),
                                            Forms\Components\TextInput::make('code')
                                                ->label('Code')
                                                ->required(),
                                        ]),

                                    Forms\Components\Select::make('contract_type_id')
                                        ->label('Type de Contrat')
                                        ->relationship('contractType', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->nullable(),
                                ])
                                ->columns(2),
                        ]),

                    // ÉTAPE 4 : CATÉGORIE, ÉCHELON & INDICE
                    Forms\Components\Wizard\Step::make('Grille Salariale')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            Forms\Components\Section::make('Classification')
                                ->description('Catégorie, échelon et indice selon la grille de la fonction publique')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('category')
                                                ->label('Catégorie')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(12)
                                                ->required()
                                                ->helperText('1 à 12'),

                                            Forms\Components\TextInput::make('current_echelon')
                                                ->label('Échelon')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(15)
                                                ->default(1)
                                                ->required()
                                                ->helperText('1 à 15'),

                                            Forms\Components\TextInput::make('indice')
                                                ->label('Indice')
                                                ->numeric()
                                                ->minValue(100)
                                                ->maxValue(1200)
                                                ->helperText('Ex: 350, 450, 600'),
                                        ]),

                                    Forms\Components\Placeholder::make('classification_display')
                                        ->label('Classification Complète')
                                        ->content(function ($get) {
                                            $category = $get('category');
                                            $echelon = $get('current_echelon');
                                            $indice = $get('indice');

                                            if ($category && $echelon) {
                                                $text = "Catégorie {$category} / Échelon {$echelon}";
                                                if ($indice) {
                                                    $text .= " / Indice {$indice}";
                                                }
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="text-lg font-bold text-primary-600">' . $text . '</div>'
                                                );
                                            }
                                            return 'Classification sera affichée après saisie';
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('category_recruitment')
                                        ->label('Classification au Recrutement')
                                        ->placeholder('Ex: 7/1')
                                        ->maxLength(255)
                                        ->helperText('Catégorie/Échelon initial'),

                                    Forms\Components\DatePicker::make('echelon_start_date')
                                        ->label('Date Début Échelon Actuel')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->default(now()),

                                    Forms\Components\DatePicker::make('last_advancement_date')
                                        ->label('Dernier Avancement')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->disabled()
                                        ->dehydrated(false),
                                ])
                                ->columns(2),
                        ]),

                    // ÉTAPE 5 : DATES & CARRIÈRE
                    Forms\Components\Wizard\Step::make('Dates Importantes')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Forms\Components\Section::make('Carrière')
                                ->schema([
                                    Forms\Components\DatePicker::make('recruitment_date')
                                        ->label('Date de Recrutement')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),

                                    Forms\Components\DatePicker::make('service_start_date')
                                        ->label('Date de Prise de Service')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),

                                    Forms\Components\TextInput::make('retirement_age')
                                        ->label('Âge de Départ à la Retraite')
                                        ->numeric()
                                        ->default(60)
                                        ->minValue(55)
                                        ->maxValue(70)
                                        ->suffix('ans')
                                        ->helperText('Date calculée automatiquement'),

                                    Forms\Components\DatePicker::make('retirement_date')
                                        ->label('Date de Retraite Prévue')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->disabled()
                                        ->dehydrated(false),
                                ])
                                ->columns(2),
                        ]),

                    // ÉTAPE 6 : INFORMATIONS BANCAIRES & ADMINISTRATIVES
                    Forms\Components\Wizard\Step::make('Banque & Documents')
                        ->icon('heroicon-o-building-library')
                        ->schema([
                            Forms\Components\Section::make('Informations Bancaires')
                                ->schema([
                                    Forms\Components\TextInput::make('bank_account_number')
                                        ->label('Numéro de Compte Bancaire')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('bank_name')
                                        ->label('Nom de la Banque')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('contract_number')
                                        ->label('N° Contrat/Décision de Recrutement')
                                        ->maxLength(255),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Discipline')
                                ->schema([
                                    Forms\Components\TextInput::make('disciplinary_points')
                                        ->label('Points Disciplinaires')
                                        ->numeric()
                                        ->step(0.5)
                                        ->default(0.0)
                                        ->minValue(0)
                                        ->suffix('points')
                                        ->helperText('0 = Aucune sanction. Peut être décimal (ex: 0.5, 1.5)'),

                                    Forms\Components\Textarea::make('disciplinary_notes')
                                        ->label('Notes Disciplinaires')
                                        ->rows(2)
                                        ->maxLength(65535)
                                        ->placeholder('Historique des sanctions ou observations'),
                                ])
                                ->columns(1)
                                ->collapsible(),
                        ]),

                    // ÉTAPE 7 : STATUT
                    Forms\Components\Wizard\Step::make('Statut')
                        ->icon('heroicon-o-check-badge')
                        ->schema([
                            Forms\Components\Section::make('État du Dossier')
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('Statut de l\'Employé')
                                        ->options([
                                            'active' => 'Actif',
                                            'on_leave' => 'En Congé',
                                            'retired' => 'Retraité',
                                            'suspended' => 'Suspendu',
                                            'terminated' => 'Contrat Résilié',
                                        ])
                                        ->default('active')
                                        ->required()
                                        ->native(false),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Compte Actif dans le Système')
                                        ->default(true)
                                        ->helperText('Désactiver si l\'employé ne doit plus accéder au système'),
                                ])
                                ->columns(2),
                        ]),

                    // ÉTAPE 8 : QR CODE & BIOMÉTRIE (visible uniquement en édition)
                    Forms\Components\Wizard\Step::make('QR Code & Biométrie')
                        ->icon('heroicon-o-qr-code')
                        ->schema([
                            Forms\Components\Section::make('Identification Numérique')
                                ->schema([
                                    Forms\Components\Placeholder::make('qr_code_preview')
                                        ->label('QR Code de l\'Employé')
                                        ->content(function ($record) {
                                            if ($record && $record->qr_code_path) {
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="flex flex-col items-center gap-3">
                                                    <img src="' . \Storage::url($record->qr_code_path) . '" 
                                                         alt="QR Code" 
                                                         class="w-64 h-64 border-2 border-gray-300 rounded-lg shadow-md">
                                                    <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded">
                                                        <strong>Matricule:</strong> ' . $record->matricule . '<br>
                                                        <strong>Généré le:</strong> ' . $record->updated_at->format('d/m/Y à H:i') . '
                                                    </div>
                                                </div>'
                                                );
                                            }
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-center p-6 bg-blue-50 rounded-lg">
                                                <p class="text-blue-800">✨ Le QR Code sera généré automatiquement après la création</p>
                                            </div>'
                                            );
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('qr_code_data')
                                        ->label('Données Encodées dans le QR Code')
                                        ->rows(4)
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn($record) => $record && $record->qr_code_data),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Toggle::make('fingerprint_enrolled')
                                                ->label('Empreintes Digitales Enregistrées')
                                                ->disabled()
                                                ->dehydrated(false),

                                            Forms\Components\Placeholder::make('fingerprint_enrolled_at')
                                                ->label('Date Enregistrement Biométrique')
                                                ->content(fn($record) => $record && $record->fingerprint_enrolled_at
                                                    ? $record->fingerprint_enrolled_at->format('d/m/Y à H:i')
                                                    : '❌ Non enregistré'),
                                        ]),
                                ]),
                        ])
                        ->visible(fn($context) => $context === 'edit'),
                ])
                    ->columnSpanFull()
                    ->persistStepInQueryString()
                    ->skippable(),
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

                Tables\Columns\ImageColumn::make('qr_code_path')
                    ->label('QR Code')
                    ->disk('public')
                    ->width(40)
                    ->height(40)
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Tables\Actions\Action::make('generate_professional_card')
                    ->label('Carte Pro')
                    ->icon('heroicon-o-identification')
                    ->color('info')
                    ->visible(fn($record) => !$record->hasActiveProfessionalCard())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            // Vérifier carte existante
                            $existingCard = \App\Models\EmployeeCard::where('employee_id', $record->id)
                                ->where('card_type', 'professional')
                                ->where('is_active', true)
                                ->first();

                            if ($existingCard) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Carte déjà existante')
                                    ->warning()
                                    ->body('Carte N° ' . $existingCard->card_number)
                                    ->send();
                                return;
                            }

                            // Créer la carte
                            $card = \App\Models\EmployeeCard::create([
                                'employee_id' => $record->id,
                                'card_type' => 'professional',
                                'issue_date' => now(),
                                'expiry_date' => now()->addYears(5),
                                'status' => 'issued',
                            ]);

                            $card->generateCardNumber();
                            $card->generateQrCode();

                            // Générer le PDF
                            $pdfService = new \App\Services\CardPdfService();
                            $pdfPath = $pdfService->generateProfessionalCard($card);

                            $card->activate();

                            \Filament\Notifications\Notification::make()
                                ->title('Carte professionnelle créée')
                                ->success()
                                ->body('N° ' . $card->card_number)
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download')
                                        ->label('Télécharger PDF')
                                        ->url(\Storage::url($pdfPath))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erreur')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();

                            \Log::error('Erreur création carte', [
                                'employee_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }),

                Tables\Actions\Action::make('generate_health_card')
                    ->label('Carte Santé')
                    ->icon('heroicon-o-heart')
                    ->color('success')
                    ->visible(fn($record) => !$record->hasActiveHealthCard())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $card = \App\Models\EmployeeCard::create([
                            'employee_id' => $record->id,
                            'card_type' => 'health_coverage',
                            'issue_date' => now(),
                            'expiry_date' => now()->addYear(),
                            'status' => 'issued',
                        ]);

                        $card->generateCardNumber();
                        $card->generateQrCode();
                        $card->activate();

                        \Filament\Notifications\Notification::make()
                            ->title('Carte de prise en charge créée')
                            ->success()
                            ->body('N° ' . $card->card_number)
                            ->send();
                    }),
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
            RelationManagers\AssignmentHistoryRelationManager::class,
            RelationManagers\AdvancementHistoryRelationManager::class,
            RelationManagers\DependentsRelationManager::class,
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
