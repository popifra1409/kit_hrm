<?php

namespace App\Filament\Resources\LeaveDecisionResource\Pages;

use App\Filament\Resources\LeaveDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveDecisions extends ListRecords
{
    protected static string $resource = LeaveDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
