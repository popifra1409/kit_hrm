<?php

namespace App\Filament\Resources\CensusSubmissionResource\Pages;

use App\Filament\Resources\CensusSubmissionResource;
use App\Models\CensusSubmission;
use App\Models\Department;
use App\Models\Dependent;
use App\Models\Direction;
use App\Models\EmployeeDiploma;
use App\Models\JobTitle;
use App\Models\Qualification;
use App\Models\Sector;
use App\Models\Service;
use App\Models\SubDirection;
use App\Models\TradeBody;
use App\Support\OrganizationalAssignment;
use App\Services\CensusValidationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ReviewCensusSubmission extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CensusSubmissionResource::class;

    protected static string $view = 'filament.resources.census-submission-resource.pages.review-census-submission';

    public CensusSubmission $record;

    public ?array $data = [];

    public function mount(CensusSubmission $record): void
    {
        abort_unless(auth()->user()->can('view_census_submissions'), 403);

        $this->record = $record->load(['employee', 'campaign']);

        // Pré-remplit le formulaire éditable avec les valeurs SOUMISES par l'employé
        // (pas les valeurs actuelles de la fiche employé). N'importe quel valideur,
        // à n'importe quelle étape, peut corriger ces valeurs après vérification
        // physique des documents — la correction est conservée pour les étapes
        // suivantes et appliquée telle quelle à la validation finale.
        $payload = $this->record->payload ?? [];

        $organizational = $payload['organizational'] ?? [];

        // Soumission antérieure à la chaîne d'affectation : on part de l'affectation
        // ACTUELLE de l'employé (réappliquer ses propres valeurs est sans effet), pour
        // que le valideur voie — et puisse corriger — une chaîne complète.
        if (!array_key_exists('declared_branch_type', $organizational)) {
            foreach (OrganizationalAssignment::currentFor($this->record->employee) as $key => $value) {
                $organizational["declared_{$key}"] ??= $value;
            }
        }

        $this->form->fill([
            'personal' => $payload['personal'] ?? [],
            'organizational' => $organizational,
        ]);
    }

    public function getTitle(): string
    {
        return 'Recensement — ' . $this->record->employee?->full_name;
    }

    public function form(Form $form): Form
    {
        $canEdit = !$this->record->isValidated();

        return $form
            ->schema([
                Forms\Components\Section::make('🧑‍💼 Carrière — Informations Personnelles (modifiable)')
                    ->description('Corrigez ici après vérification physique des documents de l\'employé. Vos corrections sont conservées pour les étapes suivantes et appliquées à la validation finale.')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('personal.matricule_fonction_publique')->label('Matricule Fonction Publique'),
                            Forms\Components\TextInput::make('personal.id_card_number')->label("N° Carte d'identité"),
                            Forms\Components\TextInput::make('personal.last_name')->label('Nom'),
                            Forms\Components\TextInput::make('personal.first_name')->label('Prénom'),
                            Forms\Components\Select::make('personal.gender')->label('Sexe')->options(['M' => 'Masculin', 'F' => 'Féminin'])->native(false),
                            Forms\Components\DatePicker::make('personal.birth_date')->label('Date de naissance')->native(false)->displayFormat('d-m-Y'),
                            Forms\Components\Select::make('personal.marital_status')
                                ->label('Statut marital')
                                ->options([
                                    'single' => 'Célibataire',
                                    'married' => 'Marié(e)',
                                    'divorced' => 'Divorcé(e)',
                                    'widowed' => 'Veuf/Veuve',
                                ])
                                ->native(false),
                            Forms\Components\TextInput::make('personal.children_under_6')->label('Enfants < 6 ans')->numeric(),
                            Forms\Components\TextInput::make('personal.total_children')->label('Total enfants')->numeric(),
                            Forms\Components\DatePicker::make('personal.recruitment_date')->label('Date de recrutement')->native(false)->displayFormat('d-m-Y'),
                            Forms\Components\DatePicker::make('personal.service_start_date')->label('Date de prise de service')->native(false)->displayFormat('d-m-Y'),
                            Forms\Components\TextInput::make('personal.phone')->label('Téléphone'),
                            Forms\Components\TextInput::make('personal.email')->label('Email')->email(),
                            Forms\Components\TextInput::make('personal.address')->label('Adresse'),
                            Forms\Components\TextInput::make('personal.city')->label('Ville'),
                        ]),
                    ])
                    ->disabled(!$canEdit)
                    ->dehydrated()
                    ->collapsible(),

                Forms\Components\Section::make('🧑‍💼 Carrière — Classification Salariale (modifiable)')
                    ->description("L'indice sera recalculé automatiquement à la validation finale à partir de la catégorie/échelon ci-dessous — inutile de le saisir directement.")
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('personal.category_number')->label('Catégorie'),
                            Forms\Components\TextInput::make('personal.echelon_number')->label('Échelon'),
                        ]),
                    ])
                    ->disabled(!$canEdit)
                    ->dehydrated()
                    ->collapsible(),

                Forms\Components\Section::make('💰 Solde — Banque & CNPS (modifiable)')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('personal.bank_name')->label('Banque'),
                            Forms\Components\TextInput::make('personal.bank_account_number')->label('N° de compte'),
                            Forms\Components\TextInput::make('personal.cnps_number')->label('N° CNPS'),
                        ]),
                    ])
                    ->disabled(!$canEdit)
                    ->dehydrated()
                    ->collapsible(),

                Forms\Components\Section::make('🏥 Carrière — Affectation organisationnelle (modifiable, appliquée à la validation finale)')
                    ->description("Branche médicale : Direction → Département → Service → Secteur. Branche administrative : Direction → Sous-direction → Service → Secteur. Chaque liste se filtre selon le niveau précédent.")
                    ->schema([
                        Forms\Components\ToggleButtons::make('organizational.declared_branch_type')
                            ->label("Branche d'affectation")
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
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => self::resetBelow($set, ['direction', 'department', 'sub_direction', 'service', 'sector']))
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('organizational.declared_direction_id')
                                ->label('Direction')
                                ->options(fn(Forms\Get $get) => self::directionOptions($get))
                                ->getOptionLabelUsing(fn($value) => Direction::find($value)?->name)
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->disabled(fn(Forms\Get $get) => !$get(self::ORG . 'branch_type') && !$get(self::ORG . 'direction_id'))
                                ->afterStateUpdated(fn(Forms\Set $set) => self::resetBelow($set, ['department', 'sub_direction', 'service', 'sector'])),

                            Forms\Components\Select::make('organizational.declared_department_id')
                                ->label('Département')
                                ->visible(fn(Forms\Get $get) => $get(self::ORG . 'branch_type') === OrganizationalAssignment::BRANCH_MEDICAL)
                                ->options(fn(Forms\Get $get) => self::departmentOptions($get))
                                ->getOptionLabelUsing(fn($value) => Department::find($value)?->name)
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    self::resetBelow($set, ['service', 'sector']);

                                    // Choisir un département remplit sa direction si elle est connue.
                                    if ($state && !$get(self::ORG . 'direction_id')) {
                                        $directionId = Department::find($state)?->direction_id;
                                        if ($directionId) {
                                            $set(self::ORG . 'direction_id', $directionId);
                                        }
                                    }
                                }),

                            Forms\Components\Select::make('organizational.declared_sub_direction_id')
                                ->label('Sous-direction')
                                ->visible(fn(Forms\Get $get) => $get(self::ORG . 'branch_type') === OrganizationalAssignment::BRANCH_ADMINISTRATIVE)
                                ->options(fn(Forms\Get $get) => self::subDirectionOptions($get))
                                ->getOptionLabelUsing(fn($value) => SubDirection::find($value)?->name)
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->disabled(fn(Forms\Get $get) => !$get(self::ORG . 'direction_id') && !$get(self::ORG . 'sub_direction_id'))
                                ->afterStateUpdated(fn(Forms\Set $set) => self::resetBelow($set, ['service', 'sector'])),

                            Forms\Components\Select::make('organizational.declared_service_id')
                                ->label('Service')
                                ->options(fn(Forms\Get $get) => self::serviceOptions($get))
                                ->getOptionLabelUsing(fn($value) => Service::find($value)?->name)
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->disabled(fn(Forms\Get $get) => !$get(self::ORG . 'service_id')
                                    && !$get(self::ORG . 'department_id')
                                    && !$get(self::ORG . 'sub_direction_id'))
                                ->afterStateUpdated(fn(Forms\Set $set) => self::resetBelow($set, ['sector'])),

                            Forms\Components\Select::make('organizational.declared_sector_id')
                                ->label('Secteur / Unité')
                                ->visible(fn(Forms\Get $get) => (bool) $get(self::ORG . 'service_id'))
                                ->options(fn(Forms\Get $get) => self::sectorOptions($get))
                                ->getOptionLabelUsing(fn($value) => Sector::find($value)?->name)
                                ->searchable()
                                ->native(false)
                                ->helperText('Optionnel : seulement pour les services qui ont des secteurs ou unités.'),
                        ]),
                    ])
                    ->disabled(!$canEdit)
                    ->dehydrated()
                    ->collapsible(),

                Forms\Components\Section::make('🧑‍💼 Carrière — Corps de métier, qualification & poste (modifiable, appliqué à la validation finale)')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('organizational.declared_trade_body_id')
                                ->label('Corps de métier')
                                ->options(fn(Forms\Get $get) => self::withSelected(
                                    TradeBody::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
                                    TradeBody::class,
                                    $get(self::ORG . 'trade_body_id'),
                                    ' (inactif)'
                                ))
                                ->getOptionLabelUsing(fn($value) => TradeBody::find($value)?->name)
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(fn(Forms\Set $set) => self::resetBelow($set, ['qualification'])),

                            Forms\Components\Select::make('organizational.declared_qualification_id')
                                ->label('Qualification')
                                ->options(fn(Forms\Get $get) => self::qualificationOptions($get))
                                ->getOptionLabelUsing(fn($value) => Qualification::find($value)?->name)
                                ->searchable()
                                ->native(false)
                                ->disabled(fn(Forms\Get $get) => !$get(self::ORG . 'trade_body_id') && !$get(self::ORG . 'qualification_id'))
                                ->helperText('Choisissez d\'abord le corps de métier.'),

                            Forms\Components\Select::make('organizational.declared_job_title_id')
                                ->label('Poste hiérarchique')
                                ->options(fn(Forms\Get $get) => self::withSelected(
                                    JobTitle::where('is_active', true)->orderBy('hierarchy_level')->get()
                                        ->mapWithKeys(fn($job) => [$job->id => "{$job->name} (Niveau {$job->hierarchy_level})"])->all(),
                                    JobTitle::class,
                                    $get(self::ORG . 'job_title_id'),
                                    ' (inactif)'
                                ))
                                ->getOptionLabelUsing(fn($value) => JobTitle::find($value)?->name)
                                ->searchable()
                                ->native(false),

                            Forms\Components\Select::make('organizational.declared_personnel_type')
                                ->label('Type de personnel')
                                ->options(OrganizationalAssignment::PERSONNEL_TYPES)
                                ->native(false),

                            Forms\Components\Select::make('organizational.declared_administrative_status')
                                ->label('Statut administratif')
                                ->options(OrganizationalAssignment::ADMINISTRATIVE_STATUSES)
                                ->native(false),
                        ]),
                    ])
                    ->disabled(!$canEdit)
                    ->dehydrated()
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    /**
     * Enregistre les corrections du formulaire dans le payload de la soumission,
     * sans toucher aux ayants droit/diplômes/photo qui n'y figurent pas. Appelé
     * juste avant chaque action de validation (Carrière/Solde/Finale).
     */
    protected function persistEditedPayload(): void
    {
        $formState = $this->form->getState();

        // Log temporaire de diagnostic — à retirer une fois le problème confirmé résolu.
        \Illuminate\Support\Facades\Log::info('CensusSubmission — état du formulaire avant enregistrement', [
            'submission_id' => $this->record->id,
            'form_state' => $formState,
        ]);

        $this->record->update([
            'payload' => array_merge($this->record->payload ?? [], [
                'personal' => array_merge($this->record->payload['personal'] ?? [], $formState['personal'] ?? []),
                'organizational' => $this->mergeOrganizational($formState['organizational'] ?? []),
            ]),
        ]);

        $this->record->refresh();
    }

    // ------------------------------------------------------------------
    // Chaîne d'affectation : listes filtrées (mêmes règles que le recensement)
    // ------------------------------------------------------------------

    /** Préfixe des champs d'affectation dans l'état du formulaire. */
    private const ORG = 'organizational.declared_';

    /** Champs d'affectation dont le formulaire de cette page fait foi. */
    private const ORG_KEYS = [
        'declared_branch_type',
        'declared_direction_id',
        'declared_department_id',
        'declared_sub_direction_id',
        'declared_service_id',
        'declared_sector_id',
        'declared_trade_body_id',
        'declared_qualification_id',
        'declared_job_title_id',
        'declared_personnel_type',
        'declared_administrative_status',
    ];

    /**
     * Pour les champs d'affectation, le formulaire fait foi : une clé absente de son
     * état (champ masqué ou désactivé, par exemple le département quand on passe en
     * branche administrative) doit devenir null, et non conserver une ancienne
     * valeur du payload qui rendrait la chaîne incohérente.
     */
    protected function mergeOrganizational(array $formOrganizational): array
    {
        $organizational = $this->record->payload['organizational'] ?? [];

        foreach (self::ORG_KEYS as $key) {
            $organizational[$key] = $formOrganizational[$key] ?? null;
        }

        return $organizational;
    }

    /** Remet à vide les niveaux situés sous celui qui vient de changer. */
    private static function resetBelow(Forms\Set $set, array $levels): void
    {
        foreach ($levels as $level) {
            $set(self::ORG . "{$level}_id", null);
        }
    }

    private static function pluckNames($query): array
    {
        return $query->orderBy('order')->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Garde l'élément déjà choisi dans la liste même s'il n'y est plus rattaché
     * (donnée ancienne, élément désactivé) : sans cela il disparaîtrait de l'écran
     * ou bloquerait l'enregistrement sans que le valideur comprenne pourquoi.
     */
    private static function withSelected(array $options, string $model, $selectedId, string $suffix = ' (hors hiérarchie)'): array
    {
        if ($selectedId && !array_key_exists($selectedId, $options)) {
            $name = $model::find($selectedId)?->name;

            if ($name) {
                $options = [$selectedId => $name . $suffix] + $options;
            }
        }

        return $options;
    }

    private static function directionOptions(Forms\Get $get): array
    {
        // Selon les enfants réels, pas selon la colonne "type" de la direction :
        // des départements en branche médicale, des sous-directions en branche administrative.
        $branch = $get(self::ORG . 'branch_type');

        $query = Direction::active();
        if ($branch === OrganizationalAssignment::BRANCH_MEDICAL) {
            $query->whereHas('departments', fn($q) => $q->where('is_active', true));
        } elseif ($branch === OrganizationalAssignment::BRANCH_ADMINISTRATIVE) {
            $query->whereHas('subDirections', fn($q) => $q->where('is_active', true));
        } else {
            $query->whereRaw('1 = 0');
        }

        $options = self::pluckNames($query);

        return self::withSelected($options, Direction::class, $get(self::ORG . 'direction_id'));
    }

    private static function departmentOptions(Forms\Get $get): array
    {
        // La direction filtre quand elle est choisie ; sinon tous les départements sont
        // proposés pour que ceux qui n'ont pas de direction restent accessibles.
        $direction = $get(self::ORG . 'direction_id');
        $options = self::pluckNames(
            $direction ? Department::active()->where('direction_id', $direction) : Department::active()
        );

        return self::withSelected($options, Department::class, $get(self::ORG . 'department_id'));
    }

    private static function subDirectionOptions(Forms\Get $get): array
    {
        $direction = $get(self::ORG . 'direction_id');
        $options = $direction ? self::pluckNames(SubDirection::active()->where('direction_id', $direction)) : [];

        return self::withSelected($options, SubDirection::class, $get(self::ORG . 'sub_direction_id'));
    }

    private static function serviceOptions(Forms\Get $get): array
    {
        $branch = $get(self::ORG . 'branch_type');
        $options = [];

        if ($branch === OrganizationalAssignment::BRANCH_MEDICAL && ($department = $get(self::ORG . 'department_id'))) {
            $options = self::pluckNames(
                Service::active()->where('type', 'medical')->where('department_id', $department)
            );
        } elseif ($branch === OrganizationalAssignment::BRANCH_ADMINISTRATIVE && ($subDirection = $get(self::ORG . 'sub_direction_id'))) {
            $options = self::pluckNames(
                Service::active()
                    ->whereIn('type', OrganizationalAssignment::ADMINISTRATIVE_SERVICE_TYPES)
                    ->where('sub_direction_id', $subDirection)
            );
        }

        return self::withSelected($options, Service::class, $get(self::ORG . 'service_id'));
    }

    private static function sectorOptions(Forms\Get $get): array
    {
        $service = $get(self::ORG . 'service_id');
        $options = $service ? self::pluckNames(Sector::active()->where('service_id', $service)) : [];

        return self::withSelected($options, Sector::class, $get(self::ORG . 'sector_id'));
    }

    private static function qualificationOptions(Forms\Get $get): array
    {
        $tradeBody = $get(self::ORG . 'trade_body_id');
        $options = $tradeBody
            ? Qualification::where('trade_body_id', $tradeBody)->where('is_active', true)
            ->orderBy('level_rank')->pluck('name', 'id')->all()
            : [];

        return self::withSelected($options, Qualification::class, $get(self::ORG . 'qualification_id'));
    }

    protected function getHeaderActions(): array
    {
        $service = app(CensusValidationService::class);

        return [
            // --- Étape 1/3 : Carrière ---
            Actions\Action::make('validate_career')
                ->label('✅ Valider la Carrière')
                ->color('success')
                ->visible(fn() => $this->record->isAwaitingCareer() && auth()->user()->can('validate_census_career'))
                ->requiresConfirmation()
                ->modalDescription('Enregistre vos éventuelles corrections ci-dessus, puis transmet le dossier à l\'étape Solde pour validation du salaire/CNPS.')
                ->action(function () use ($service) {
                    $this->persistEditedPayload();
                    $service->validateCareer($this->record, auth()->id());
                    $this->record->refresh();
                    $this->mount($this->record);
                    Notification::make()->title('Carrière validée')->body('Transmis à l\'étape Solde.')->success()->send();
                }),

            Actions\Action::make('reject_career')
                ->label('❌ Rejeter la Carrière')
                ->color('danger')
                ->visible(fn() => $this->record->isAwaitingCareer() && auth()->user()->can('reject_census_career'))
                ->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('reason')->label('Motif du rejet')->required()])
                ->action(function (array $data) use ($service) {
                    $service->rejectCareer($this->record, auth()->id(), $data['reason']);
                    $this->record->refresh();
                    Notification::make()->title('Carrière rejetée')->warning()->send();
                }),

            // --- Étape 2/3 : Solde ---
            Actions\Action::make('validate_solde')
                ->label('✅ Valider la Solde')
                ->color('success')
                ->visible(fn() => $this->record->isAwaitingSolde() && auth()->user()->can('validate_census_solde'))
                ->requiresConfirmation()
                ->modalDescription('Enregistre vos éventuelles corrections ci-dessus, puis transmet le dossier à l\'administrateur pour le contrôle final.')
                ->action(function () use ($service) {
                    $this->persistEditedPayload();
                    $service->validateSolde($this->record, auth()->id());
                    $this->record->refresh();
                    $this->mount($this->record);
                    Notification::make()->title('Solde validée')->body('Transmis pour contrôle final.')->success()->send();
                }),

            Actions\Action::make('reject_solde')
                ->label('❌ Rejeter la Solde')
                ->color('danger')
                ->visible(fn() => $this->record->isAwaitingSolde() && auth()->user()->can('reject_census_solde'))
                ->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('reason')->label('Motif du rejet')->required()])
                ->action(function (array $data) use ($service) {
                    $service->rejectSolde($this->record, auth()->id(), $data['reason']);
                    $this->record->refresh();
                    Notification::make()->title('Solde rejetée')->warning()->send();
                }),

            // --- Étape 3/3 : Contrôle final administrateur ---
            Actions\Action::make('validate_final')
                ->label('✅ Valider Définitivement')
                ->color('success')
                ->visible(fn() => $this->record->isAwaitingFinalAdmin() && auth()->user()->can('validate_census_submissions'))
                ->requiresConfirmation()
                ->modalDescription("Enregistre vos éventuelles corrections ci-dessus, puis applique réellement toutes les informations : personnelles, bancaires/CNPS, classification salariale, affectation organisationnelle, ayants droit et diplômes.")
                ->action(function () use ($service) {
                    try {
                        $this->persistEditedPayload();
                        $service->apply($this->record, auth()->id());
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('Validation impossible')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $this->record->refresh();
                    $this->mount($this->record);
                    Notification::make()->title('Recensement validé définitivement')->success()->send();
                }),

            Actions\Action::make('reject_final')
                ->label('❌ Rejeter Définitivement')
                ->color('danger')
                ->visible(fn() => $this->record->isAwaitingFinalAdmin() && auth()->user()->can('reject_census_submissions'))
                ->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('reason')->label('Motif du rejet')->required()])
                ->action(function (array $data) use ($service) {
                    $service->reject($this->record, auth()->id(), $data['reason']);
                    $this->record->refresh();
                    Notification::make()->title('Recensement rejeté')->warning()->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        $employee = $this->record->employee;
        $payload = $this->record->payload;
        $service = app(CensusValidationService::class);

        $existingDependents = $employee->dependents->keyBy('id');
        $newDependents = collect();
        $updatedDependents = collect();

        foreach ($payload['dependents'] ?? [] as $item) {
            if (!empty($item['existing_id']) && $existingDependents->has($item['existing_id'])) {
                $updatedDependents->push(['old' => $existingDependents[$item['existing_id']], 'new' => $item]);
            } else {
                $newDependents->push($item);
            }
        }

        $existingDiplomas = $employee->diplomas->keyBy('id');
        $newDiplomas = collect();
        $updatedDiplomas = collect();

        foreach ($payload['diplomas'] ?? [] as $item) {
            if (!empty($item['existing_id']) && $existingDiplomas->has($item['existing_id'])) {
                $updatedDiplomas->push(['old' => $existingDiplomas[$item['existing_id']], 'new' => $item]);
            } else {
                $newDiplomas->push($item);
            }
        }

        return [
            'payload' => $payload,
            'employee' => $employee,
            'newDependents' => $newDependents,
            'updatedDependents' => $updatedDependents,
            'unaddressedDependents' => !$this->record->isValidated() ? $service->getUnaddressedDependents($this->record) : collect(),
            'newDiplomas' => $newDiplomas,
            'updatedDiplomas' => $updatedDiplomas,
            'unaddressedDiplomas' => !$this->record->isValidated() ? $service->getUnaddressedDiplomas($this->record) : collect(),
        ];
    }

    public function deactivateDependent(int $dependentId): void
    {
        abort_unless(auth()->user()->can('validate_census_submissions'), 403);

        Dependent::where('id', $dependentId)
            ->where('employee_id', $this->record->employee_id)
            ->update(['is_active' => false]);

        Notification::make()->title('Ayant droit désactivé')->success()->send();
    }

    public function deactivateDiploma(int $diplomaId): void
    {
        abort_unless(auth()->user()->can('validate_census_submissions'), 403);

        EmployeeDiploma::where('id', $diplomaId)
            ->where('employee_id', $this->record->employee_id)
            ->delete();

        Notification::make()->title('Diplôme retiré')->success()->send();
    }
}
