<?php

namespace App\Filament\Resources\SalaryGridResource\Pages;

use App\Filament\Resources\SalaryGridResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalaryGrid extends EditRecord
{
    protected static string $resource = SalaryGridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
