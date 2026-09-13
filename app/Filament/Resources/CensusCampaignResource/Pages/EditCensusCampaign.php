<?php

namespace App\Filament\Resources\CensusCampaignResource\Pages;

use App\Filament\Resources\CensusCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCensusCampaign extends EditRecord
{
    protected static string $resource = CensusCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Supprimer'),
        ];
    }
}
