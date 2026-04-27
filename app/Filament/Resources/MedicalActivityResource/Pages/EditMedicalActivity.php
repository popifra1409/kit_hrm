<?php

namespace App\Filament\Resources\MedicalActivityResource\Pages;

use App\Filament\Resources\MedicalActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMedicalActivity extends EditRecord
{
    protected static string $resource = MedicalActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
