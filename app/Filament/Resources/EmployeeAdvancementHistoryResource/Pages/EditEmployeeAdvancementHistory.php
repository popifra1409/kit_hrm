<?php

namespace App\Filament\Resources\EmployeeAdvancementHistoryResource\Pages;

use App\Filament\Resources\EmployeeAdvancementHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeAdvancementHistory extends EditRecord
{
    protected static string $resource = EmployeeAdvancementHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
