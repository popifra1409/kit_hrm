<?php

namespace App\Filament\Resources\EmployeeAffectationResource\Pages;

use App\Filament\Resources\EmployeeAffectationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeAffectation extends EditRecord
{
    protected static string $resource = EmployeeAffectationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
