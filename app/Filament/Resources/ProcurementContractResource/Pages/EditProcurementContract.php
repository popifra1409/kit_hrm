<?php

namespace App\Filament\Resources\ProcurementContractResource\Pages;

use App\Filament\Resources\ProcurementContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcurementContract extends EditRecord
{
    protected static string $resource = ProcurementContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
