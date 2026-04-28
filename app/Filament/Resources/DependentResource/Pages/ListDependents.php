<?php

namespace App\Filament\Resources\DependentResource\Pages;

use App\Filament\Resources\DependentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDependents extends ListRecords
{
    protected static string $resource = DependentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('tree_view')
                ->label('Vue Arborescente')
                ->icon('heroicon-o-folder-open')
                ->color('info')
                ->url(fn(): string => static::$resource::getUrl('tree')),

            Actions\CreateAction::make()
                ->label('Créer'),
        ];
    }
}
