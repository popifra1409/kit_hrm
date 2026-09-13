<?php

namespace App\Filament\Resources\CensusCampaignResource\Pages;

use App\Filament\Resources\CensusCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCensusCampaigns extends ListRecords
{
    protected static string $resource = CensusCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle campagne'),
        ];
    }
}
