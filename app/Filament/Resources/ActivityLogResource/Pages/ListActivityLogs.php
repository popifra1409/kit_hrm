<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Console\Commands\ArchiveActivityLogs;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Les ' . ArchiveActivityLogs::RETENTION_MONTHS . ' derniers mois en ligne, puis versement mensuel aux archives. '
            . 'Les échecs et les blocages sont consignés comme les succès.';
    }
}
