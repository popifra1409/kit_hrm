<?php

namespace App\Filament\Resources\ReplacementResource\Pages;

use App\Filament\Resources\ReplacementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReplacement extends EditRecord
{
    protected static string $resource = ReplacementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
