<?php

namespace App\Filament\Resources\EmployeeAssignmentHistoryResource\Pages;

use App\Filament\Resources\EmployeeAssignmentHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeAssignmentHistory extends EditRecord
{
    protected static string $resource = EmployeeAssignmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
