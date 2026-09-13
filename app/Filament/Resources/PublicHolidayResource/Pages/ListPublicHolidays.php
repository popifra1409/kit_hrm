<?php

namespace App\Filament\Resources\PublicHolidayResource\Pages;

use App\Filament\Resources\PublicHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPublicHolidays extends ListRecords
{
    protected static string $resource = PublicHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Ajouter un jour férié'),
        ];
    }
}
