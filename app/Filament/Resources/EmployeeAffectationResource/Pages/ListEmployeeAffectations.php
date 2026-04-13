<?php

namespace App\Filament\Resources\EmployeeAffectationResource\Pages;

use App\Filament\Resources\EmployeeAffectationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeAffectations extends ListRecords
{
    protected static string $resource = EmployeeAffectationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
