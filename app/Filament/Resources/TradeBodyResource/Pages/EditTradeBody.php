<?php

namespace App\Filament\Resources\TradeBodyResource\Pages;

use App\Filament\Resources\TradeBodyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTradeBody extends EditRecord
{
    protected static string $resource = TradeBodyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
