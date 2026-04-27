<?php

namespace App\Filament\Resources\QuotpartDistributionResource\Pages;

use App\Filament\Resources\QuotpartDistributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotpartDistributions extends ListRecords
{
    protected static string $resource = QuotpartDistributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
