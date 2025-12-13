<?php

namespace App\Filament\Resources\ProcurementTypeResource\Pages;

use App\Filament\Resources\ProcurementTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcurementTypes extends ListRecords
{
    protected static string $resource = ProcurementTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
