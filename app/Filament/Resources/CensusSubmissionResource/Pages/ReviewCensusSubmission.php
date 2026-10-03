<?php

namespace App\Filament\Resources\CensusSubmissionResource\Pages;

use App\Filament\Resources\CensusSubmissionResource;
use App\Models\CensusSubmission;
use App\Models\Dependent;
use App\Models\EmployeeDiploma;
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

        $this->form->fill([
            'personal' => $payload['personal'] ?? [],
            'organizational' => $payload['organizational'] ?? [],
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
                            Forms\Components\TextInput::make('personal.first_name')->label('Prénom'),
                            Forms\Components\TextInput::make('personal.last_name')->label('Nom'),
                            Forms\Components\Select::make('personal.gender')->label('Sexe')->options(['M' => 'Masculin', 'F' => 'Féminin'])->native(false),
                            Forms\Components\DatePicker::make('personal.birth_date')->label('Date de naissance')->native(false)->displayFormat('d/m/Y'),
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
                            Forms\Components\DatePicker::make('personal.recruitment_date')->label('Date de recrutement')->native(false)->displayFormat('d/m/Y'),
                            Forms\Components\DatePicker::make('personal.service_start_date')->label('Date de prise de service')->native(false)->displayFormat('d/m/Y'),
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

                Forms\Components\Section::make('🧑‍💼 Carrière — Classification (modifiable, appliquée à la validation finale)')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('organizational.declared_trade_body_id')
                                ->label('Corps de métier')
                                ->options(fn() => \App\Models\TradeBody::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->native(false),
                            Forms\Components\Select::make('organizational.declared_qualification_id')
                                ->label('Qualification')
                                ->options(fn() => \App\Models\Qualification::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->native(false),
                            Forms\Components\Select::make('organizational.declared_job_title_id')
                                ->label('Poste')
                                ->options(fn() => \App\Models\JobTitle::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->native(false),
                            Forms\Components\Select::make('organizational.declared_personnel_type')
                                ->label('Type de personnel')
                                ->options([
                                    'soignant' => 'Soignant',
                                    'non_soignant' => 'Non Soignant',
                                    'paramedical' => 'Paramédical',
                                    'autres' => 'Autres',
                                ])
                                ->native(false),
                            Forms\Components\Select::make('organizational.declared_administrative_status')
                                ->label('Statut administratif')
                                ->options([
                                    'fonctionnaire_affecte' => 'Fonctionnaire Affecté',
                                    'fonctionnaire_detache' => 'Fonctionnaire Détaché',
                                    'contractuel_structure' => 'Contractuel de la Structure',
                                ])
                                ->native(false),
                        ]),
                    ])
                    ->disabled(!$canEdit)
                    ->dehydrated()
                    ->collapsible(),

                Forms\Components\Section::make('🧑‍💼 Carrière — Affectation Déclarée (modifiable, jamais appliquée)')
                    ->description('Toujours déclaratif — même corrigées ici, ces valeurs ne sont jamais appliquées automatiquement à la fiche employé.')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('organizational.declared_department')->label('Département'),
                            Forms\Components\TextInput::make('organizational.declared_service')->label('Service'),
                        ]),
                    ])
                    ->disabled(!$canEdit)
                    ->dehydrated()
                    ->collapsible()
                    ->collapsed(),
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
                'organizational' => array_merge($this->record->payload['organizational'] ?? [], $formState['organizational'] ?? []),
            ]),
        ]);

        $this->record->refresh();
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
                ->modalDescription('Enregistre vos éventuelles corrections ci-dessus, puis applique réellement toutes les informations personnelles, bancaires/CNPS, ayants droit et diplômes. Les informations organisationnelles déclarées restent à vérifier manuellement.')
                ->action(function () use ($service) {
                    $this->persistEditedPayload();
                    $service->apply($this->record, auth()->id());
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
