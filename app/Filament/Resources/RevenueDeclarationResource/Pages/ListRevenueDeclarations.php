<?php

namespace App\Filament\Resources\RevenueDeclarationResource\Pages;

use App\Filament\Resources\RevenueDeclarationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevenueDeclarations extends ListRecords
{
    protected static string $resource = RevenueDeclarationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
