<?php

namespace App\Filament\Resources\LeaveDecisionResource\Pages;

use App\Filament\Resources\LeaveDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveDecision extends EditRecord
{
    protected static string $resource = LeaveDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
