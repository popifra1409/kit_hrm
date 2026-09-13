<?php

namespace App\Filament\Resources\LeaveApprovalStepResource\Pages;

use App\Filament\Resources\LeaveApprovalStepResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveApprovalStep extends EditRecord
{
    protected static string $resource = LeaveApprovalStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Supprimer'),
        ];
    }
}
