<?php

namespace App\Filament\Resources\MedicalDepartmentResource\Pages;

use App\Filament\Resources\MedicalDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMedicalDepartment extends EditRecord
{
    protected static string $resource = MedicalDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
