<?php

namespace App\Filament\Resources\CnpsPreRegistrationResource\Pages;

use App\Filament\Resources\CnpsPreRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCnpsPreRegistration extends CreateRecord
{
    protected static string $resource = CnpsPreRegistrationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
