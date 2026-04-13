<?php

namespace App\Filament\Resources\PerformanceEvaluationResource\Pages;

use App\Filament\Resources\PerformanceEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerformanceEvaluations extends ListRecords
{
    protected static string $resource = PerformanceEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
