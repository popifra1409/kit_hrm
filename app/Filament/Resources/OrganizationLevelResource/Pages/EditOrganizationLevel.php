<?php

namespace App\Filament\Resources\OrganizationLevelResource\Pages;

use App\Filament\Resources\OrganizationLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationLevel extends EditRecord
{
    protected static string $resource = OrganizationLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
