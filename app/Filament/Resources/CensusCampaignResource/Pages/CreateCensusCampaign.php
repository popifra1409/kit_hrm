<?php

namespace App\Filament\Resources\CensusCampaignResource\Pages;

use App\Filament\Resources\CensusCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCensusCampaign extends CreateRecord
{
    protected static string $resource = CensusCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
