<?php

namespace App\Filament\Resources\QuotpartPeriodResource\Pages;

use App\Filament\Resources\QuotpartPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuotpartPeriod extends ViewRecord
{
    protected static string $resource = QuotpartPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
