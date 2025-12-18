<?php

namespace App\Filament\Resources\SignatoryResource\Pages;

use App\Filament\Resources\SignatoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignatories extends ListRecords
{
    protected static string $resource = SignatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
