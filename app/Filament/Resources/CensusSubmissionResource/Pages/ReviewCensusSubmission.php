<?php

namespace App\Filament\Resources\CensusSubmissionResource\Pages;

use App\Filament\Resources\CensusSubmissionResource;
use App\Models\CensusSubmission;
use App\Models\Dependent;
use App\Models\EmployeeDiploma;
use App\Services\CensusValidationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ReviewCensusSubmission extends Page
{
    protected static string $resource = CensusSubmissionResource::class;

    protected static string $view = 'filament.resources.census-submission-resource.pages.review-census-submission';

    public CensusSubmission $record;

    public function mount(CensusSubmission $record): void
    {
        abort_unless(auth()->user()->can('view_census_submissions'), 403);

        $this->record = $record->load(['employee', 'campaign']);
    }

    public function getTitle(): string
    {
        return 'Recensement — ' . $this->record->employee?->full_name;
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
                ->modalDescription('Transmet le dossier à l\'étape Solde pour validation du salaire/CNPS.')
                ->action(function () use ($service) {
                    $service->validateCareer($this->record, auth()->id());
                    $this->record->refresh();
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
                ->modalDescription('Transmet le dossier à l\'administrateur pour le contrôle final.')
                ->action(function () use ($service) {
                    $service->validateSolde($this->record, auth()->id());
                    $this->record->refresh();
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
                ->modalDescription('Applique réellement toutes les informations personnelles, bancaires/CNPS, ayants droit et diplômes. Les informations organisationnelles déclarées restent à vérifier manuellement.')
                ->action(function () use ($service) {
                    $service->apply($this->record, auth()->id());
                    $this->record->refresh();
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

        $personalNew = $payload['personal'] ?? [];
        $personalOld = [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'gender' => $employee->gender,
            'birth_date' => $employee->birth_date?->format('Y-m-d'),
            'marital_status' => $employee->marital_status,
            'children_under_6' => $employee->children_under_6,
            'total_children' => $employee->total_children,
            'id_card_number' => $employee->id_card_number,
            'recruitment_date' => $employee->recruitment_date?->format('Y-m-d'),
            'service_start_date' => $employee->service_start_date?->format('Y-m-d'),
            'phone' => $employee->phone,
            'email' => $employee->email,
            'address' => $employee->address,
            'city' => $employee->city,
            'bank_name' => $employee->bank_name,
            'bank_account_number' => $employee->bank_account_number,
            'cnps_number' => $employee->cnps_number,
        ];

        $organizationalDeclared = $payload['organizational'] ?? [];
        $organizationalCurrent = [
            'current_department' => $employee->department?->name ?? $employee->currentService?->department?->name ?? null,
            'current_service' => $employee->currentService?->name ?? $employee->service?->name,
            'current_job_title' => $employee->jobTitle?->name,
            'current_trade_body' => $employee->tradeBody?->name,
            'current_qualification' => $employee->qualification?->name,
            'current_personnel_type' => $employee->personnel_type,
            'current_administrative_status' => $employee->administrative_status_label,
        ];

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
            'personalOld' => $personalOld,
            'personalNew' => $personalNew,
            'organizationalCurrent' => $organizationalCurrent,
            'organizationalDeclared' => $organizationalDeclared,
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
