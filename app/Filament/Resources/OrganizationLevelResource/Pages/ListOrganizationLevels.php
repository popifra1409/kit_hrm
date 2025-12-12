<?php

namespace App\Filament\Resources\OrganizationLevelResource\Pages;

use App\Filament\Resources\OrganizationLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationLevels extends ListRecords
{
    protected static string $resource = OrganizationLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
