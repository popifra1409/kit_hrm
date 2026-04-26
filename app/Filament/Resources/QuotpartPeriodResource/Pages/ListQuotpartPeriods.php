<?php

namespace App\Filament\Resources\QuotpartPeriodResource\Pages;

use App\Filament\Resources\QuotpartPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotpartPeriods extends ListRecords
{
    protected static string $resource = QuotpartPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
