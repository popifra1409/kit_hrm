<?php

namespace App\Filament\Resources\EmployeeHierarchyResource\Pages;

use App\Filament\Resources\EmployeeHierarchyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeHierarchies extends ListRecords
{
    protected static string $resource = EmployeeHierarchyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
