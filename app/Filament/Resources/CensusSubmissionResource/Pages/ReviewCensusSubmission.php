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
        return [
            Actions\Action::make('validate')
                ->label('Valider le recensement')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->isSubmitted() && auth()->user()->can('validate_census_submissions'))
                ->requiresConfirmation()
                ->modalDescription('Le profil (dont banque/CNPS), les ayants droit et les diplômes seront mis à jour. L\'affectation déclarée par l\'employé ne sera PAS appliquée automatiquement — à vérifier manuellement contre les archives.')
                ->action(function () {
                    app(CensusValidationService::class)->apply($this->record, auth()->id());
                    $this->record->refresh();

                    Notification::make()
                        ->title('Recensement validé')
                        ->body('Profil, ayants droit et diplômes mis à jour. Pensez à vérifier l\'affectation déclarée.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Rejeter')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->record->isSubmitted() && auth()->user()->can('reject_census_submissions'))
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Motif du rejet')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'rejected',
                        'rejected_by' => auth()->id(),
                        'rejected_at' => now(),
                        'rejection_reason' => $data['reason'],
                    ]);

                    Notification::make()->title('Recensement rejeté')->warning()->send();

                    $this->redirect(CensusSubmissionResource::getUrl('index'));
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
            'current_department' => $employee->department,
            'current_service' => $employee->service,
            'current_job_title' => $employee->job_title,
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
            'personalOld' => $personalOld,
            'personalNew' => $personalNew,
            'organizationalCurrent' => $organizationalCurrent,
            'organizationalDeclared' => $organizationalDeclared,
            'newDependents' => $newDependents,
            'updatedDependents' => $updatedDependents,
            'unaddressedDependents' => $this->record->isSubmitted() ? $service->getUnaddressedDependents($this->record) : collect(),
            'newDiplomas' => $newDiplomas,
            'updatedDiplomas' => $updatedDiplomas,
            'unaddressedDiplomas' => $this->record->isSubmitted() ? $service->getUnaddressedDiplomas($this->record) : collect(),
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
