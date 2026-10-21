<?php

namespace App\Filament\Resources\AccountActivationRequestResource\Pages;

use App\Filament\Resources\AccountActivationRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountActivationRequests extends ListRecords
{
    protected static string $resource = AccountActivationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
