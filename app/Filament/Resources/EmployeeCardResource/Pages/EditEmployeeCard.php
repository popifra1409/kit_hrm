<?php

namespace App\Filament\Resources\EmployeeCardResource\Pages;

use App\Filament\Resources\EmployeeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeCard extends EditRecord
{
    protected static string $resource = EmployeeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
