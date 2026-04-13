<?php

namespace App\Filament\Resources\EmployeeAdvancementHistoryResource\Pages;

use App\Filament\Resources\EmployeeAdvancementHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeAdvancementHistories extends ListRecords
{
    protected static string $resource = EmployeeAdvancementHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
