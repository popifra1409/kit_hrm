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
            Actions\DeleteAction::make(),

            Actions\Action::make('view_qr_code')
                ->label('Voir QR Code')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->visible(fn($record) => $record->qr_code_path)
                ->modalContent(fn($record) => view('filament.modals.qr-code-view', [
                    'employee' => $record
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fermer'),
        ];
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

                                    Forms\Components\Select::make('position_id')
                                        ->label('Position / Poste')
                                        ->relationship(
                                            'position',
                                            'name',
                                            fn($query) =>
                                            $query->where('is_active', true)->orderBy('level_rank')
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->required()
                                        ->getOptionLabelFromRecordUsing(
                                            fn($record) =>
                                            '🏆 ' . $record->name . ' (Niveau ' . $record->level_rank . ')'
                                        ),
                                ]),
                        ]),

                    // TAB 3 : EMPLOI ET CONTRAT
                    Forms\Components\Tabs\Tab::make('Emploi')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('employment_type')
                                        ->label('Type d\'Emploi')
                                        ->options([
                                            'permanent' => 'Permanent',
                                            'contract' => 'Contractuel',
                                            'temporary' => 'Temporaire',
                                            'intern' => 'Stagiaire',
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
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('category_recruitment')
                                        ->label('Catégorie Recrutement')
                                        ->maxLength(50),

                                    Forms\Components\TextInput::make('category_current')
                                        ->label('Catégorie Actuelle')
                                        ->maxLength(50),

                                    Forms\Components\TextInput::make('qualification')
                                        ->label('Qualification')
                                        ->maxLength(255),
                                ]),

                            Forms\Components\Fieldset::make('Classification Salariale')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('category_number')
                                                ->label('Catégorie')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(12)  // CHANGÉ
                                                ->suffix('/ 12')  // CHANGÉ
                                                ->reactive()
                                                ->live(),

                                            Forms\Components\TextInput::make('echelon_number')
                                                ->label('Échelon')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(12)  // CHANGÉ
                                                ->suffix('/ 12')  // CHANGÉ
                                                ->reactive()
                                                ->live(),

                                            Forms\Components\TextInput::make('indice')
                                                ->label('Indice')
                                                ->numeric()
                                                ->minValue(100)
                                                ->maxValue(1200)
                                                ->placeholder('Ex: 350, 450')
                                                ->reactive()
                                                ->live(),
                                        ]),

                                    Forms\Components\Placeholder::make('salary_display')
                                        ->label('💰 Salaire de Base')
                                        ->content(function (Forms\Get $get) {
                                            $category = $get('category_number');
                                            $echelon = $get('echelon_number');
                                            $indice = $get('indice');

                                            if ($category && $echelon) {
                                                try {
                                                    $baseSalary = \App\Models\SalaryGrid::getBaseSalary($category, $echelon);

                                                    $html = '<div class="p-3 bg-green-50 rounded-lg">
                                    <p class="text-lg font-bold text-green-900">' .
                                                        number_format($baseSalary, 0, ',', ' ') . ' FCFA</p>
                                    <p class="text-sm text-green-700">Cat. ' . $category .
                                                        ' / Éch. ' . $echelon;

                                                    if ($indice) {
                                                        $html .= ' / Ind. ' . $indice;
                                                    }

                                                    $html .= '</p></div>';

                                                    return new \Illuminate\Support\HtmlString($html);
                                                } catch (\Exception $e) {
                                                    return 'Non disponible';
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
                            Forms\Components\Placeholder::make('qr_code_preview')
                                ->label('QR Code')
                                ->content(function ($record) {
                                    if ($record && $record->qr_code_path) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex flex-col items-center gap-3">
                                                <img src="' . \Storage::url($record->qr_code_path) . '" 
                                                     alt="QR Code" 
                                                     class="w-64 h-64 border-2 border-gray-300 rounded-lg shadow">
                                                <div class="text-sm text-gray-600">
                                                    <strong>Matricule:</strong> ' . $record->matricule . '
                                                </div>
                                            </div>'
                                        );
                                    }
                                    return 'QR Code non généré';
                                })
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

                    // TAB 8 : DISCIPLINE
                    Forms\Components\Tabs\Tab::make('Discipline')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('disciplinary_points')
                                        ->label('Points Disciplinaires')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->step(0.5)
                                        ->suffix('points'),
                                ]),

                            Forms\Components\Textarea::make('disciplinary_notes')
                                ->label('Notes Disciplinaires')
                                ->rows(4)
                                ->maxLength(65535)
                                ->columnSpanFull(),
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
