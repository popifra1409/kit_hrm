<?php

namespace App\Filament\Resources\SalaryGridResource\Pages;

use App\Filament\Resources\SalaryGridResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalaryGrid extends EditRecord
{
    protected static string $resource = SalaryGridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // S'assurer que category et echelon sont des strings
        $data['category'] = (string) $data['category'];
        $data['echelon'] = (string) $data['echelon'];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // S'assurer que category et echelon restent des strings
        $data['category'] = (string) $data['category'];
        $data['echelon'] = (string) $data['echelon'];

        return $data;
    }
}
