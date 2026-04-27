<?php

namespace App\Filament\Resources\QuotpartDeductionTypeResource\Pages;

use App\Filament\Resources\QuotpartDeductionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotpartDeductionType extends EditRecord
{
    protected static string $resource = QuotpartDeductionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
