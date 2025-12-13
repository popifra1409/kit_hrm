<?php

namespace App\Filament\Resources\ProcurementContractResource\Pages;

use App\Filament\Resources\ProcurementContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcurementContracts extends ListRecords
{
    protected static string $resource = ProcurementContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
