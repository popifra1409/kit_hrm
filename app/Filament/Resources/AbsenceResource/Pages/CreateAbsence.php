<?php

namespace App\Filament\Resources\AbsenceResource\Pages;

use App\Filament\Resources\AbsenceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAbsence extends CreateRecord
{
    protected static string $resource = AbsenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculer automatiquement les heures
        $absence = new \App\Models\Absence($data);
        $absence->calculateHours();

        $data['hours'] = $absence->hours;

        return $data;
    }
}
