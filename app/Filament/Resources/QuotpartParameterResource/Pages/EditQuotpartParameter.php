<?php

namespace App\Filament\Resources\QuotpartParameterResource\Pages;

use App\Filament\Resources\QuotpartParameterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotpartParameter extends EditRecord
{
    protected static string $resource = QuotpartParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
