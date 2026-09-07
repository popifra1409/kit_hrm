<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use App\Services\LeaveWorkflowService;
use Filament\Resources\Pages\CreateRecord;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function afterCreate(): void
    {
        app(LeaveWorkflowService::class)->submit($this->record);
    }
}
