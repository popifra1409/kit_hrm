<?php

namespace App\Filament\Resources\MedicalActivityResource\Pages;

use App\Filament\Resources\MedicalActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicalActivities extends ListRecords
{
    protected static string $resource = MedicalActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
