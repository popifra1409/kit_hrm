<?php

namespace App\Filament\Resources\DependentResource\Pages;

use App\Filament\Resources\DependentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDependent extends EditRecord
{
    protected static string $resource = DependentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
