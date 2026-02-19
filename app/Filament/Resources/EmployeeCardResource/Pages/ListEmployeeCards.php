<?php

namespace App\Filament\Resources\EmployeeCardResource\Pages;

use App\Filament\Resources\EmployeeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeCards extends ListRecords
{
    protected static string $resource = EmployeeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
