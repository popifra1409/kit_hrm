<?php

namespace App\Filament\Resources\SubDirectionResource\Pages;

use App\Filament\Resources\SubDirectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubDirection extends EditRecord
{
    protected static string $resource = SubDirectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
