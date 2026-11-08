<?php

namespace App\Filament\Resources\ActivityLogArchiveResource\Pages;

use App\Filament\Resources\ActivityLogArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogArchives extends ListRecords
{
    protected static string $resource = ActivityLogArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Lignes versées par la tâche activity-logs:archive, classées par mois (colonne « Mois archivé »). '
            . 'Le logiciel ne les supprime jamais.';
    }
}
