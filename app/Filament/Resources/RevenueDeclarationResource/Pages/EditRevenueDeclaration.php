<?php

namespace App\Filament\Resources\RevenueDeclarationResource\Pages;

use App\Filament\Resources\RevenueDeclarationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRevenueDeclaration extends EditRecord
{
    protected static string $resource = RevenueDeclarationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
