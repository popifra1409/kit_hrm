<?php

namespace App\Filament\Resources\CnpsPreRegistrationResource\Pages;

use App\Filament\Resources\CnpsPreRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCnpsPreRegistration extends EditRecord
{
    protected static string $resource = CnpsPreRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
