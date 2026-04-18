<?php

namespace App\Filament\Resources\SubDirectionResource\Pages;

use App\Filament\Resources\SubDirectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubDirections extends ListRecords
{
    protected static string $resource = SubDirectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
