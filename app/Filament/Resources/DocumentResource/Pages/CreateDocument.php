<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Extraire les métadonnées du fichier si non renseignées
        if (isset($data['file_path']) && !isset($data['file_name'])) {
            $data['file_name'] = basename($data['file_path']);
        }

        return $data;
    }
}
