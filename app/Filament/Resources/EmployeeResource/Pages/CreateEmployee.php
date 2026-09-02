<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Forms\Components\Wizard;

class CreateEmployee extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = EmployeeResource::class;

    protected function getSteps(): array
    {
        return [
            // ========================================
            // ÉTAPE 1 : INFORMATIONS PERSONNELLES
            // ========================================
            Wizard\Step::make('Informations Personnelles')
                ->description('Identité et état civil de l\'employé')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('matricule')
                                ->label('Matricule')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->placeholder('Ex: EMP001')
                                ->helperText('Identifiant unique de l\'employé'),

                            Forms\Components\TextInput::make('last_name')
                                ->label('Nom')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Ex: KAMGA'),

                            Forms\Components\TextInput::make('first_name')
                                ->label('Prénom(s)')
                                ->maxLength(255)
                                ->placeholder('Ex: Jean Pierre')
                                ->helperText('Optionnel'),
                        ]),

                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\DatePicker::make('birth_date')
                                ->label('Date de Naissance')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->maxDate(now()->subYears(17))
                                ->helperText('Âge minimum : 17 ans'),

                            Forms\Components\Select::make('gender')
                                ->label('Sexe')
                                ->options([
                                    'M' => 'Masculin',
                                    'F' => 'Féminin',
                                ])
                                ->required()
                                ->native(false),

                            Forms\Components\Select::make('marital_status')
                                ->label('État Civil')
                                ->options([
                                    'single' => 'Célibataire',
                                    'married' => 'Marié(e)',
                                    'divorced' => 'Divorcé(e)',
                                    'widowed' => 'Veuf(ve)',
                                ])
                                ->native(false)
                                ->default('single'),

                            Forms\Components\TextInput::make('total_children')
                                ->label('Nombre d\'Enfants')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->suffix('enfant(s)'),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('id_card_number')
                                ->label('N° Carte Nationale d\'Identité')
                                ->maxLength(50)
                                ->placeholder('Ex: 123456789'),

                            Forms\Components\FileUpload::make('photo')
                                ->label('Photo d\'identité')
                                ->image()
                                ->directory('employees/photos')
                                ->imageEditor()
                                ->maxSize(2048)
                                ->helperText('Format: JPG, PNG - Max: 2MB'),
                        ]),
                ]),

            // ========================================
            // ÉTAPE 2 : AFFECTATION ORGANISATIONNELLE
            // ========================================
            Wizard\Step::make('Affectation Organisationnelle')
                ->description('Rattachement hiérarchique et poste')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Forms\Components\ToggleButtons::make('branch_type')
                        ->label('Branche d\'Affectation')
                        ->options([
                            'medical' => 'Branche Médicale',
                            'administrative' => 'Branche Administrative',
                        ])
                        ->icons([
                            'medical' => 'heroicon-o-heart',
                            'administrative' => 'heroicon-o-building-office',
                        ])
                        ->colors([
                            'medical' => 'success',
                            'administrative' => 'primary',
                        ])
                        ->inline()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn($set) => [
                            $set('department_id', null),
                            $set('current_service_id', null),
                            $set('sector_id', null),
                        ])
                        ->live()
                        ->columnSpanFull()
                        ->default('medical'),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('department_id')
                                ->label('Département Médical')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn(Forms\Get $get) => $get('branch_type') === 'medical')
                                ->reactive()
                                ->afterStateUpdated(fn($set) => $set('current_service_id', null))
                                ->helperText('Sélectionnez le département médical'),

                            Forms\Components\Select::make('current_service_id')
                                ->label('Service')
                                ->options(function (Forms\Get $get) {
                                    if ($get('branch_type') === 'medical' && $get('department_id')) {
                                        return \App\Models\Service::where('department_id', $get('department_id'))
                                            ->where('type', 'medical')
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    } elseif ($get('branch_type') === 'administrative') {
                                        return \App\Models\Service::whereIn('type', ['administrative', 'support', 'technical'])
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->reactive()
                                ->afterStateUpdated(fn($set) => $set('sector_id', null))
                                ->helperText(
                                    fn(Forms\Get $get) =>
                                    $get('branch_type') === 'medical'
                                        ? 'Service médical du département'
                                        : 'Service administratif, support ou technique'
                                ),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('sector_id')
                                ->label('Secteur / Unité Opérationnelle')
                                ->relationship('sector', 'name', function ($query, Forms\Get $get) {
                                    if ($get('current_service_id')) {
                                        return $query->where('service_id', $get('current_service_id'))
                                            ->where('is_active', true);
                                    }
                                    return $query;
                                })
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn(Forms\Get $get) => $get('current_service_id'))
                                ->helperText('Optionnel : Secteur ou unité spécifique'),

                            Forms\Components\Select::make('trade_body_id')
                                ->label('Corps de Métier')
                                ->relationship('tradeBody', 'name', fn($query) => $query->where('is_active', true))
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->reactive()
                                ->afterStateUpdated(fn($set) => $set('qualification_id', null))
                                ->helperText('Médecin, Technicien, Agent administratif...'),

                            Forms\Components\Select::make('qualification_id')
                                ->label('Qualification')
                                ->options(function (Forms\Get $get) {
                                    if (!$get('trade_body_id')) {
                                        return [];
                                    }
                                    return \App\Models\Qualification::where('trade_body_id', $get('trade_body_id'))
                                        ->where('is_active', true)
                                        ->orderBy('level_rank')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->disabled(fn(Forms\Get $get) => !$get('trade_body_id'))
                                ->helperText('Choisissez d\'abord le corps de métier'),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('job_title_id')
                                ->label('Poste Hiérarchique')
                                ->relationship(
                                    'jobTitle',
                                    'name',
                                    fn($query) =>
                                    $query->where('is_active', true)->orderBy('hierarchy_level')
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->getOptionLabelFromRecordUsing(
                                    fn($record) =>
                                    '🏆 ' . $record->name . ' (Niveau ' . $record->hierarchy_level . ')'
                                )
                                ->helperText('Fonction et niveau hiérarchique'),
                        ]),
                ]),

            // ========================================
            // ÉTAPE 3 : EMPLOI ET CONTRAT
            // ========================================
            Wizard\Step::make('Emploi et Contrat')
                ->description('Type d\'emploi et dates clés')
                ->icon('heroicon-o-briefcase')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('administrative_status')
                                ->label('Statut Administratif')
                                ->options([
                                    'fonctionnaire_affecte' => '🏛️ Fonctionnaire Affecté',
                                    'fonctionnaire_detache' => '🔄 Fonctionnaire en Détachement',
                                    'contractuel_fp' => '📋 Contractuel de la Fonction Publique',
                                    'contractuel_structure' => '🏥 Contractuel de la Structure',
                                    'stagiaire' => '🎓 Stagiaire',
                                ])
                                ->required()
                                ->native(false)
                                ->default('contractuel_structure'),

                            Forms\Components\Select::make('personnel_type')
                                ->label('Type de Personnel')
                                ->options([
                                    'soignant' => '👨‍⚕️ Personnel Soignant',
                                    'non_soignant' => '💼 Personnel Non-Soignant',
                                    'paramedical' => '🩺 Personnel Paramédical',
                                    'autres' => '🛠️ Autres',
                                ])
                                ->native(false)
                                ->default('non_soignant')
                                ->helperText('Catégorie professionnelle'),

                            Forms\Components\Select::make('contract_type_id')
                                ->label('Type de Contrat')
                                ->relationship('contractType', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->helperText('CDD, CDI, Stage, etc.'),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('contract_number')
                                ->label('N° de Contrat')
                                ->maxLength(100)
                                ->placeholder('Ex: CT-2024-001'),

                            Forms\Components\DatePicker::make('recruitment_date')
                                ->label('Date de Recrutement')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now()),

                            Forms\Components\DatePicker::make('service_start_date')
                                ->label('Date de Prise de Service')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now()),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('retirement_age')
                                ->label('Âge de Départ à la Retraite')
                                ->numeric()
                                ->default(60)
                                ->minValue(55)
                                ->maxValue(70)
                                ->suffix('ans')
                                ->helperText('La date de retraite sera calculée automatiquement'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Employé Actif')
                                ->default(true)
                                ->inline(false)
                                ->helperText('Désactiver si l\'employé n\'est plus en service'),
                        ]),
                ]),

            // ========================================
            // ÉTAPE 4 : CATÉGORIE, ÉCHELON & INDICE
            // ========================================
            Wizard\Step::make('Catégorie, Échelon & Indice')
                ->description('Classification salariale selon la grille')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\Grid::make(1)
                        ->schema([
                            Forms\Components\TextInput::make('category_recruitment')
                                ->label('Catégorie de Recrutement/Echelon')
                                ->maxLength(50)
                                ->placeholder('Ex: A1, B2 ou 1, 2')
                                ->helperText('Classification initiale. La catégorie actuelle est définie ci-dessous dans la grille salariale.'),
                        ]),

                    Forms\Components\Section::make('Grille Salariale')
                        ->schema([
                            // 🎯 SÉLECTEUR TYPE DE CLASSIFICATION
                            Forms\Components\Radio::make('classification_type')
                                ->label('Type de Classification')
                                ->options([
                                    'cameroon' => '🇨🇲 Nomenclature Camerounaise (Fonctionnaires: A1, B2, C3, etc.)',
                                    'numeric' => '🔢 Classification Numérique (Contractuels: 1-12)',
                                ])
                                ->default('cameroon')
                                ->reactive()
                                ->live()
                                ->inline()
                                ->required(),

                            // ✅ OPTION 1 : NOMENCLATURE CAMEROUNAISE
                            Forms\Components\Grid::make(3)
                                ->visible(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                ->schema([
                                    Forms\Components\Select::make('category_number')
                                        ->label('Catégorie')
                                        ->options(\App\Enums\EmployeeClassification::getCategoryOptions())
                                        ->searchable()
                                        ->reactive()
                                        ->live()
                                        ->helperText('A, B, C, D, E')
                                        ->required(fn(Forms\Get $get) => $get('classification_type') === 'cameroon'),

                                    Forms\Components\Select::make('echelon_number')
                                        ->label('Échelon')
                                        ->options(\App\Enums\EmployeeClassification::getEchelonOptions())
                                        ->searchable()
                                        ->reactive()
                                        ->live()
                                        ->helperText('1 à 8')
                                        ->required(fn(Forms\Get $get) => $get('classification_type') === 'cameroon'),

                                    Forms\Components\TextInput::make('indice')
                                        ->label('Indice')
                                        ->numeric()
                                        ->minValue(100)
                                        ->placeholder('Ex: 350, 450, 600')
                                        ->helperText('100 à 1600')
                                        ->reactive()
                                        ->live(),
                                ]),

                            Forms\Components\Placeholder::make('classification_cameroon_display')
                                ->visible(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                ->label('📋 Classification Camerounaise')
                                ->content(function (Forms\Get $get) {
                                    $category = $get('category_number');
                                    $echelon = $get('echelon_number');

                                    if ($category && $echelon) {
                                        $classification = "{$category}{$echelon}";
                                        $label = \App\Enums\EmployeeClassification::getLabel($classification);

                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg">
                                    <p class="text-xl font-bold text-blue-900">' . $classification . '</p>
                                    <p class="text-sm text-blue-700">' . $label . '</p>
                                    ' . ($get('indice') ? '<p class="text-sm text-blue-700 mt-2">🔢 Indice: ' . $get('indice') . '</p>' : '') . '
                                </div>'
                                        );
                                    }

                                    return '<p class="text-gray-500">Sélectionnez catégorie et échelon</p>';
                                })
                                ->columnSpanFull(),

                            // ✅ OPTION 2 : CLASSIFICATION NUMÉRIQUE (1-12)
                            Forms\Components\Grid::make(3)
                                ->visible(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                                ->schema([
                                    Forms\Components\Select::make('category_number')
                                        ->label('Catégorie')
                                        ->options(array_combine(range(1, 12), range(1, 12)))
                                        ->searchable()
                                        ->reactive()
                                        ->live()
                                        ->suffix('/ 12')
                                        ->helperText('1 à 12')
                                        ->required(fn(Forms\Get $get) => $get('classification_type') === 'numeric'),

                                    Forms\Components\Select::make('echelon_number')
                                        ->label('Échelon')
                                        ->options(array_combine(range(1, 12), range(1, 12)))
                                        ->searchable()
                                        ->reactive()
                                        ->live()
                                        ->suffix('/ 12')
                                        ->helperText('1 à 12')
                                        ->required(fn(Forms\Get $get) => $get('classification_type') === 'numeric'),

                                    Forms\Components\TextInput::make('indice')
                                        ->label('Indice')
                                        ->numeric()
                                        ->minValue(100)
                                        ->maxValue(1600)
                                        ->placeholder('Ex: 350, 450, 600')
                                        ->helperText('100 à 1600')
                                        ->reactive()
                                        ->live(),
                                ]),

                            Forms\Components\Placeholder::make('classification_numeric_display')
                                ->visible(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                                ->label('📋 Classification Numérique')
                                ->content(function (Forms\Get $get) {
                                    $category = $get('category_number');
                                    $echelon = $get('echelon_number');

                                    if ($category && $echelon) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-lg">
                                    <p class="text-xl font-bold text-purple-900">
                                        Catégorie ' . $category . ' / Échelon ' . $echelon . '
                                    </p>
                                    <p class="text-sm text-purple-700">Classification numérique (Contractuel)</p>
                                    ' . ($get('indice') ? '<p class="text-sm text-purple-700 mt-2">🔢 Indice: ' . $get('indice') . '</p>' : '') . '
                                </div>'
                                        );
                                    }

                                    return '<p class="text-gray-500">Sélectionnez catégorie et échelon</p>';
                                })
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('echelon_start_date')
                                ->label('Date Début Échelon Actuel')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Date d\'entrée dans l\'échelon actuel'),

                            Forms\Components\DatePicker::make('last_advancement_date')
                                ->label('Dernier Avancement')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Dernière promotion/avancement'),
                        ]),

                    // 💰 AFFICHAGE DU SALAIRE (Peu importe la nomenclature)
                    Forms\Components\Placeholder::make('salary_info')
                        ->label('💰 Informations Salariales')
                        ->content(function (Forms\Get $get) {
                            $category = $get('category_number');
                            $echelon = $get('echelon_number');
                            $indice = $get('indice');
                            $type = $get('classification_type');

                            if ($category && $echelon) {
                                try {
                                    $baseSalary = \App\Models\SalaryGrid::getBaseSalary($category, $echelon);

                                    $classification = $type === 'cameroon'
                                        ? $category . $echelon
                                        : "Cat. {$category} / Éch. {$echelon}";

                                    $html = '<div class="p-4 bg-green-50 border border-green-200 rounded-lg space-y-2">
                            <p class="text-lg font-bold text-green-900">
                                💵 Salaire de Base : ' . number_format($baseSalary, 0, ',', ' ') . ' FCFA
                            </p>
                            <p class="text-sm text-green-700">
                                📊 ' . $classification;

                                    if ($indice) {
                                        $html .= ' - Indice ' . $indice;
                                    }

                                    $html .= '</p></div>';

                                    return new \Illuminate\Support\HtmlString($html);
                                } catch (\Exception $e) {
                                    return '<div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700">
                            ⚠️ Grille salariale non disponible pour cette classification
                        </div>';
                                }
                            }

                            return '<p class="text-gray-500">Sélectionnez une catégorie et un échelon pour voir le salaire de base</p>';
                        })
                        ->columnSpanFull(),
                ]),

            // ========================================
            // ÉTAPE 5 : COORDONNÉES
            // ========================================
            Wizard\Step::make('Coordonnées')
                ->description('Contact et adresse')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('phone')
                                ->label('Téléphone')
                                ->tel()
                                ->maxLength(20)
                                ->placeholder('Ex: +237 6XX XXX XXX')
                                ->prefix('📱'),

                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255)
                                ->placeholder('Ex: prenom.nom@chuy.cm')
                                ->prefix('📧'),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Textarea::make('address')
                                ->label('Adresse Complète')
                                ->rows(3)
                                ->maxLength(500)
                                ->placeholder('Rue, quartier, ville...')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('city')
                                ->label('Ville')
                                ->maxLength(100)
                                ->placeholder('Ex: Douala, Yaoundé')
                                ->columnSpan(1),
                        ]),
                ]),

            // ========================================
            // ÉTAPE 6 : INFORMATIONS BANCAIRES & CNPS
            // ========================================
            Wizard\Step::make('Informations Bancaires & CNPS')
                ->description('Compte bancaire et numéros administratifs')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Forms\Components\Section::make('Informations Bancaires')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('bank_name')
                                        ->label('Nom de la Banque')
                                        ->maxLength(255)
                                        ->placeholder('Ex: UBA, BICEC, Ecobank')
                                        ->prefix('🏦'),

                                    Forms\Components\TextInput::make('bank_account_number')
                                        ->label('N° de Compte Bancaire')
                                        ->maxLength(255)
                                        ->placeholder('Ex: 10033123456789')
                                        ->prefix('💳'),
                                ]),
                        ])
                        ->collapsible(),

                    Forms\Components\Section::make('Numéros Administratifs')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('cnps_number')
                                        ->label('N° CNPS')
                                        ->maxLength(255)
                                        ->placeholder('Ex: 1234567')
                                        ->helperText('Caisse Nationale de Prévoyance Sociale'),

                                    Forms\Components\TextInput::make('numero_contribuable')
                                        ->label('N° Contribuable')
                                        ->maxLength(255)
                                        ->placeholder('Ex: M012345678')
                                        ->helperText('Numéro fiscal'),

                                    Forms\Components\TextInput::make('matricule_interne')
                                        ->label('Matricule Interne')
                                        ->maxLength(255)
                                        ->placeholder('Ex: INT-001')
                                        ->helperText('Matricule interne hôpital'),
                                ]),
                        ])
                        ->collapsible(),
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Employé créé avec succès ! 🎉';
    }

    protected function afterCreate(): void
    {
        $employee = $this->record;

        if ($employee && !$employee->qr_code_path) {
            try {
                if (class_exists(\App\Services\QrCodeService::class)) {
                    $qrCodeService = new \App\Services\QrCodeService();
                    $qrCodeService->generateEmployeeQrCode($employee);

                    \Filament\Notifications\Notification::make()
                        ->title('QR Code généré')
                        ->success()
                        ->body('Le QR Code de l\'employé a été créé automatiquement.')
                        ->send();
                }
            } catch (\Exception $e) {
                \Log::error('Erreur génération QR Code après création employé', [
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}