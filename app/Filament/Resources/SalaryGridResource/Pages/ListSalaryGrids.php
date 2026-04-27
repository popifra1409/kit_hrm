<?php

namespace App\Filament\Resources\SalaryGridResource\Pages;

use App\Filament\Resources\SalaryGridResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalaryGrids extends ListRecords
{
    protected static string $resource = SalaryGridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('matrix_view')
                ->label('Vue Matricielle')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn(): string => static::$resource::getUrl('matrix')),

            Actions\CreateAction::make()
                ->label('Créer'),
        ];
    }
}
