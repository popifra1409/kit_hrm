<?php

namespace App\Filament\Resources\QuotpartDistributionResource\Pages;

use App\Filament\Resources\QuotpartDistributionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotpartDistribution extends EditRecord
{
    protected static string $resource = QuotpartDistributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
