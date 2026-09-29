<?php

namespace App\Filament\Resources\AccountDeletionResource\Pages;

use App\Filament\Resources\AccountDeletionResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountDeletions extends ListRecords
{
    protected static string $resource = AccountDeletionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
