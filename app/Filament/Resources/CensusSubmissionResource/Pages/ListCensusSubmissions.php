<?php

namespace App\Filament\Resources\CensusSubmissionResource\Pages;

use App\Filament\Resources\CensusSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListCensusSubmissions extends ListRecords
{
    protected static string $resource = CensusSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
