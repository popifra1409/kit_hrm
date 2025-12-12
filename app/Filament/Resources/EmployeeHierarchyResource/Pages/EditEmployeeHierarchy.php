<?php

namespace App\Filament\Resources\EmployeeHierarchyResource\Pages;

use App\Filament\Resources\EmployeeHierarchyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeHierarchy extends EditRecord
{
    protected static string $resource = EmployeeHierarchyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
