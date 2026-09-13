<?php

namespace App\Filament\Resources\LeaveApprovalStepResource\Pages;

use App\Filament\Resources\LeaveApprovalStepResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveApprovalSteps extends ListRecords
{
    protected static string $resource = LeaveApprovalStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Ajouter une étape'),
        ];
    }
}
