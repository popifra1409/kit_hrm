<?php

namespace App\Filament\Resources\CnpsPreRegistrationResource\Pages;

use App\Filament\Resources\CnpsPreRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCnpsPreRegistrations extends ListRecords
{
    protected static string $resource = CnpsPreRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
