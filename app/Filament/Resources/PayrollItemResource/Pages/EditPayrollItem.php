<?php

namespace App\Filament\Resources\PayrollItemResource\Pages;

use App\Filament\Resources\PayrollItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayrollItem extends EditRecord
{
    protected static string $resource = PayrollItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
