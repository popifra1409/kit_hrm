<?php

namespace App\Filament\Resources\EmployeeAssignmentHistoryResource\Pages;

use App\Filament\Resources\EmployeeAssignmentHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeAssignmentHistories extends ListRecords
{
    protected static string $resource = EmployeeAssignmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
