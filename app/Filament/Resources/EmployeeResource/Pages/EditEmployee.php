<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ✅ BOUTON GÉNÉRATION - ACTIF SEULEMENT SI PAS DE QR CODE
            Actions\Action::make('generate_qr_code')
                ->label('🔄 Générer QR Code Manquant')
                ->icon('heroicon-o-qr-code')
                ->color('warning')
                ->visible(fn() => !$this->record->qr_code_path)
                ->requiresConfirmation()
                ->modalHeading('Générer le QR Code')
                ->modalDescription('Êtes-vous sûr de vouloir générer le QR code pour cet employé ?')
                ->action(function () {
                    try {
                        $employee = $this->record;

                        // 1. Créer le contenu du QR code
                        $qrContent = json_encode([
                            'matricule' => $employee->matricule,
                            'nom' => $employee->full_name,
                            'id' => $employee->id,
                            'timestamp' => now()->toIso8601String(),
                        ]);

                        // 2. Générer l'image QR code en PNG
                        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                            ->size(300)
                            ->margin(1)
                            ->generate($qrContent);

                        // 3. Créer le dossier s'il n'existe pas
                        if (!file_exists(storage_path('app/public/qrcodes'))) {
                            mkdir(storage_path('app/public/qrcodes'), 0755, true);
                        }

                        // 4. Sauvegarder le fichier
                        $filename = 'qrcodes/employee-' . $employee->id . '.png';
                        $filePath = storage_path('app/public/' . $filename);

                        file_put_contents($filePath, $qrCode);

                        // 5. Vérifier que le fichier a été créé
                        if (!file_exists($filePath)) {
                            throw new \Exception('Impossible de créer le fichier QR code');
                        }

                        // 6. Mettre à jour l'employé
                        $employee->update([
                            'qr_code_path' => $filename,
                        ]);

                        // 7. Notification de succès
                        \Filament\Notifications\Notification::make()
                            ->title('✅ QR Code généré avec succès !')
                            ->success()
                            ->body('Le QR code est maintenant disponible.')
                            ->duration(5)
                            ->send();

                        // 8. Rafraîchir la page
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $employee->id]));
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ Erreur lors de la génération')
                            ->danger()
                            ->body('Détail: ' . $e->getMessage())
                            ->send();

                        \Log::error('QR Code generation error', [
                            'employee_id' => $employee->id ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }),

            // ✅ BADGE INFO - AFFICHE SI QR CODE EXISTE
            Actions\Action::make('qr_code_exists')
                ->label('✅ QR Code Existant')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->qr_code_path)
                ->disabled()
                ->tooltip('Le QR code existe déjà'),

            Actions\DeleteAction::make(),
        ];
    }

    // ✅ AJOUTER : Récupérer les données avant remplissage du formulaire
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // S'assurer que les champs sont bien des strings
        $data['category_number'] = (string) ($data['category_number'] ?? '');
        $data['echelon_number'] = (string) ($data['echelon_number'] ?? '');

        // ✅ DÉTERMINER LA BRANCHE CORRECTEMENT
        // Si le record a un department_id → médical
        // Sinon → administratif
        if ($this->record && $this->record->department_id) {
            $data['branch_type'] = 'medical';
        } else {
            $data['branch_type'] = 'administrative';
        }

        // Déterminer le type de classification s'il n'existe pas
        if (!isset($data['classification_type']) || !$data['classification_type']) {
            // Si category_number est une lettre (A, B, C, etc.) → cameroon
            $data['classification_type'] = preg_match('/^[A-E]$/', $data['category_number'] ?? '') ? 'cameroon' : 'numeric';
        }

        return $data;
    }

    // ✅ AJOUTER : Préparer les données avant sauvegarde
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // S'assurer que category_number et echelon_number restent des strings
        $data['category_number'] = (string) $data['category_number'];
        $data['echelon_number'] = (string) $data['echelon_number'];

        // S'assurer que classification_type a une valeur
        if (!isset($data['classification_type']) || !$data['classification_type']) {
            $data['classification_type'] = 'numeric';
        }

        // ⚠️ IMPORTANT : Ne pas sauvegarder branch_type (ce n'est pas une colonne BD)
        // On la supprime pour éviter les erreurs
        unset($data['branch_type']);

        return $data;
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Informations')
                ->tabs([
                    // TAB 1 : INFORMATIONS PERSONNELLES
                    Forms\Components\Tabs\Tab::make('Informations Personnelles')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('matricule')
                                        ->label('Matricule')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(50),

                                    Forms\Components\TextInput::make('last_name')
                                        ->label('Nom')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('first_name')
                                        ->label('Prénom(s)')
                                        ->maxLength(255),
                                ]),

                            Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\DatePicker::make('birth_date')
                                        ->label('Date de Naissance')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->maxDate(now()->subYears(17)),

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
                                        ->native(false),

                                    Forms\Components\TextInput::make('total_children')
                                        ->label('Nombre d\'Enfants')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('id_card_number')
                                        ->label('N° CNI')
                                        ->maxLength(50),

                                    Forms\Components\FileUpload::make('photo')
                                        ->label('Photo')
                                        ->image()
                                        ->directory('employees/photos')
                                        ->imageEditor()
                                        ->maxSize(2048),
                                ]),
                        ]),

                    // TAB 2 : AFFECTATION ORGANISATIONNELLE
                    Forms\Components\Tabs\Tab::make('Affectation')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Forms\Components\ToggleButtons::make('branch_type')
                                ->label('Branche')
                                ->options([
                                    'medical' => 'Médical',
                                    'administrative' => 'Administratif',
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
                                ->reactive()
                                ->afterStateUpdated(fn($set) => [
                                    $set('department_id', null),
                                    $set('current_service_id', null),
                                    $set('sector_id', null),
                                ])
                                ->live()
                                ->columnSpanFull()
                                ->default(
                                    fn($record) =>
                                    $record && $record->isMedical() ? 'medical' : 'administrative'
                                ),

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
                                        ->afterStateUpdated(fn($set) => $set('current_service_id', null)),

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
                                        ->afterStateUpdated(fn($set) => $set('sector_id', null)),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('sector_id')
                                        ->label('Secteur / Unité')
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
                                        ->visible(fn(Forms\Get $get) => $get('current_service_id')),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('trade_body_id')
                                        ->label('Corps de Métier')
                                        ->relationship('tradeBody', 'name', fn($query) => $query->where('is_active', true))
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->reactive()
                                        ->afterStateUpdated(fn($set) => $set('qualification_id', null)),

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
                                        ->disabled(fn(Forms\Get $get) => !$get('trade_body_id')),
                                ]),

                            Forms\Components\Grid::make(1)
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
                                        ),
                                ]),
                        ]),

                    // TAB 3 : EMPLOI ET CONTRAT
                    Forms\Components\Tabs\Tab::make('Emploi')
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
                                        ->native(false),

                                    Forms\Components\Select::make('personnel_type')
                                        ->label('Type de Personnel')
                                        ->options([
                                            'soignant' => 'Personnel Soignant',
                                            'non_soignant' => 'Personnel Non-Soignant',
                                            'paramedical' => 'Personnel Paramédical',
                                            'autres' => 'Autres',
                                        ])
                                        ->native(false),

                                    Forms\Components\Select::make('contract_type_id')
                                        ->label('Type de Contrat')
                                        ->relationship('contractType', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                ]),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('contract_number')
                                        ->label('N° de Contrat')
                                        ->maxLength(100),

                                    Forms\Components\DatePicker::make('recruitment_date')
                                        ->label('Date de Recrutement')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),

                                    Forms\Components\DatePicker::make('service_start_date')
                                        ->label('Date de Prise de Service')
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),
                                ]),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('retirement_age')
                                        ->label('Âge de Retraite')
                                        ->numeric()
                                        ->default(60)
                                        ->suffix('ans'),

                                    Forms\Components\Select::make('status')
                                        ->label('Statut')
                                        ->options([
                                            'active' => 'Actif',
                                            'on_leave' => 'En Congé',
                                            'retired' => 'Retraité',
                                            'suspended' => 'Suspendu',
                                            'terminated' => 'Résilié',
                                        ])
                                        ->default('active')
                                        ->native(false),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Compte Actif')
                                        ->default(true)
                                        ->inline(false),
                                ]),
                        ]),

                    // TAB 4 : CATÉGORIE, ÉCHELON & INDICE
                    Forms\Components\Tabs\Tab::make('Grille Salariale')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Forms\Components\Grid::make(1)
                                ->schema([
                                    Forms\Components\TextInput::make('category_recruitment')
                                        ->label('Catégorie Recrutement')
                                        ->maxLength(50)
                                        ->helperText('La catégorie actuelle est définie ci-dessous dans la grille salariale.'),
                                ]),

                            Forms\Components\Section::make('Classification Salariale')
                                ->schema([
                                    // ✅ AJOUTER : Champ Hidden pour classification_type
                                    Forms\Components\Hidden::make('classification_type')
                                        ->default('numeric'),

                                    // Sélecteur Type de Classification
                                    Forms\Components\Radio::make('classification_type')
                                        ->label('Type de Classification')
                                        ->options([
                                            'cameroon' => '🇨🇲 Nomenclature Camerounaise (Fonctionnaires)',
                                            'numeric' => '🔢 Classification Numérique 1-12 (Contractuels)',
                                        ])
                                        ->reactive()
                                        ->live()
                                        ->inline()
                                        ->required(),

                                    // ✅ OPTION 1 : NOMENCLATURE CAMEROUNAISE (A1, A2, B1, etc.)
                                    Forms\Components\Grid::make(3)
                                        ->visible(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                        ->schema([
                                            Forms\Components\Select::make('category_number')
                                                ->label('Catégorie')
                                                ->options(\App\Enums\EmployeeClassification::getCategoryOptions())
                                                ->searchable()
                                                ->reactive()
                                                ->live(),

                                            Forms\Components\Select::make('echelon_number')
                                                ->label('Échelon')
                                                ->options(\App\Enums\EmployeeClassification::getEchelonOptions())
                                                ->searchable()
                                                ->reactive()
                                                ->live(),

                                            Forms\Components\TextInput::make('indice')
                                                ->label('Indice')
                                                ->numeric()
                                                ->minValue(100)
                                                ->placeholder('Ex: 350')
                                                ->reactive()
                                                ->live(),
                                        ]),

                                    Forms\Components\Placeholder::make('classification_cameroon_display')
                                        ->visible(fn(Forms\Get $get) => $get('classification_type') === 'cameroon')
                                        ->label('📋 Classification')
                                        ->content(function (Forms\Get $get) {
                                            $category = $get('category_number');
                                            $echelon = $get('echelon_number');

                                            if ($category && $echelon) {
                                                $classification = "{$category}{$echelon}";
                                                $label = \App\Enums\EmployeeClassification::getLabel($classification);

                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <p class="text-lg font-bold text-blue-900">' . $classification . '</p>
                                    <p class="text-sm text-blue-700">' . $label . '</p>
                                    ' . ($get('indice') ? '<p class="text-sm text-blue-700 mt-1">🔢 Indice: ' . $get('indice') . '</p>' : '') . '
                                </div>'
                                                );
                                            }

                                            return 'Sélectionnez catégorie et échelon';
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
                                                ->helperText('Numéro de 1 à 12'),

                                            Forms\Components\Select::make('echelon_number')
                                                ->label('Échelon')
                                                ->options(array_combine(range(1, 12), range(1, 12)))
                                                ->searchable()
                                                ->reactive()
                                                ->live()
                                                ->helperText('Numéro de 1 à 12'),

                                            Forms\Components\TextInput::make('indice')
                                                ->label('Indice')
                                                ->numeric()
                                                ->minValue(100)
                                                ->placeholder('Ex: 450')
                                                ->reactive()
                                                ->live(),
                                        ]),

                                    Forms\Components\Placeholder::make('classification_numeric_display')
                                        ->visible(fn(Forms\Get $get) => $get('classification_type') === 'numeric')
                                        ->label('📋 Classification')
                                        ->content(function (Forms\Get $get) {
                                            $category = $get('category_number');
                                            $echelon = $get('echelon_number');

                                            if ($category && $echelon) {
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="p-3 bg-purple-50 rounded-lg border border-purple-200">
                                    <p class="text-lg font-bold text-purple-900">Cat. ' . $category . ' / Éch. ' . $echelon . '</p>
                                    <p class="text-sm text-purple-700">Classification numérique (Contractuel)</p>
                                    ' . ($get('indice') ? '<p class="text-sm text-purple-700 mt-1">🔢 Indice: ' . $get('indice') . '</p>' : '') . '
                                </div>'
                                                );
                                            }

                                            return 'Sélectionnez catégorie et échelon';
                                        })
                                        ->columnSpanFull(),

                                    // ✅ AFFICHAGE DU SALAIRE (Peu importe la nomenclature)
                                    Forms\Components\Placeholder::make('salary_display')
                                        ->label('💰 Salaire de Base')
                                        ->content(function (Forms\Get $get) {
                                            $category = $get('category_number');
                                            $echelon = $get('echelon_number');

                                            if ($category && $echelon) {
                                                try {
                                                    $baseSalary = \App\Models\SalaryGrid::getBaseSalary($category, $echelon);

                                                    $classification = $get('classification_type') === 'cameroon'
                                                        ? $category . $echelon
                                                        : "Cat. {$category} / Éch. {$echelon}";

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                        <p class="text-lg font-bold text-green-900">' .
                                                            number_format($baseSalary, 0, ',', ' ') . ' FCFA</p>
                                        <p class="text-sm text-green-700">Classification: ' . $classification . '</p>
                                    </div>'
                                                    );
                                                } catch (\Exception $e) {
                                                    return '<div class="p-3 bg-yellow-50 rounded-lg text-yellow-700">⚠️ Grille salariale non trouvée</div>';
                                                }
                                            }

                                            return 'Sélectionnez catégorie et échelon';
                                        })
                                        ->columnSpanFull(),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('echelon_start_date')
                                        ->label('Date Début Échelon')
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),

                                    Forms\Components\DatePicker::make('last_advancement_date')
                                        ->label('Dernier Avancement')
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),
                                ]),
                        ]),

                    // TAB 5 : COORDONNÉES
                    Forms\Components\Tabs\Tab::make('Coordonnées')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('phone')
                                        ->label('Téléphone')
                                        ->tel()
                                        ->maxLength(20),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(255),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Textarea::make('address')
                                        ->label('Adresse')
                                        ->rows(3)
                                        ->maxLength(500),

                                    Forms\Components\TextInput::make('city')
                                        ->label('Ville')
                                        ->maxLength(100),
                                ]),
                        ]),

                    // TAB 6 : BANQUE & CNPS
                    Forms\Components\Tabs\Tab::make('Banque & CNPS')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\Section::make('Informations Bancaires')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('bank_name')
                                                ->label('Banque')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('bank_account_number')
                                                ->label('N° Compte')
                                                ->maxLength(255),
                                        ]),
                                ])
                                ->collapsible(),

                            Forms\Components\Section::make('Numéros Administratifs')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('cnps_number')
                                                ->label('N° CNPS')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('numero_contribuable')
                                                ->label('N° Contribuable')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('matricule_interne')
                                                ->label('Matricule Interne')
                                                ->maxLength(255),
                                        ]),
                                ])
                                ->collapsible(),
                        ]),

                    // TAB 7 : QR CODE & BIOMÉTRIE
                    Forms\Components\Tabs\Tab::make('QR Code & Biométrie')
                        ->icon('heroicon-o-qr-code')
                        ->schema([
                            // ✅ SI PAS DE QR CODE : Afficher bouton génération
                            Forms\Components\Placeholder::make('no_qr_code')
                                ->label('⚠️ Pas de QR Code')
                                ->content(fn() => new \Illuminate\Support\HtmlString(
                                    '<div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-yellow-700 mb-3">Aucun QR code n\'a été généré pour cet employé.</p>
                    <button type="button" onclick="document.querySelector(\'[data-action=generate_qr]\')?.click()"
                        class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                        🔄 Générer le QR Code Maintenant
                    </button>
                </div>'
                                ))
                                ->visible(fn($record) => !$record?->qr_code_path)
                                ->columnSpanFull(),

                            // ✅ SI QR CODE EXISTE : Afficher l'image
                            Forms\Components\Placeholder::make('qr_code_preview')
                                ->label('✅ QR Code Généré')
                                ->content(function ($record) {
                                    if ($record && $record->qr_code_path) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex flex-col items-center gap-3">
                            <img src="' . \Storage::url($record->qr_code_path) . '" 
                                 alt="QR Code" 
                                 class="w-64 h-64 border-2 border-green-300 rounded-lg shadow bg-white p-2">
                            <div class="text-sm text-gray-600">
                                <strong>Matricule:</strong> ' . $record->matricule . '
                            </div>
                        </div>'
                                        );
                                    }
                                    return '';
                                })
                                ->visible(fn($record) => $record?->qr_code_path)
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Toggle::make('fingerprint_enrolled')
                                        ->label('Empreintes Enregistrées')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\Placeholder::make('fingerprint_enrolled_at')
                                        ->label('Date Enregistrement')
                                        ->content(fn($record) => $record && $record->fingerprint_enrolled_at
                                            ? $record->fingerprint_enrolled_at->format('d/m/Y H:i')
                                            : 'Non enregistré'),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Employé mis à jour avec succès ! ✅';
    }
}
