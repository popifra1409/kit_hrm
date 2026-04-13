<?php

namespace App\Filament\Resources\AdvancementResource\Pages;

use App\Filament\Resources\AdvancementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvancements extends ListRecords
{
    protected static string $resource = AdvancementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
