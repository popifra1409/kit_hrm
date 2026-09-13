<?php

namespace App\Filament\Resources\PublicHolidayResource\Pages;

use App\Filament\Resources\PublicHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPublicHoliday extends EditRecord
{
    protected static string $resource = PublicHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Supprimer'),
        ];
    }
}
