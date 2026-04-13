<?php

namespace App\Filament\Resources\EmployeeCardResource\Pages;

use App\Filament\Resources\EmployeeCardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeCard extends CreateRecord
{
    protected static string $resource = EmployeeCardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Les valeurs par défaut sont déjà dans le formulaire
        return $data;
    }

    protected function afterCreate(): void
    {
        $card = $this->record;

        // Générer le numéro de carte
        if (!$card->card_number) {
            $card->generateCardNumber();
        }

        // Générer le QR Code
        $card->generateQrCode();

        // Notification
        \Filament\Notifications\Notification::make()
            ->title('Carte créée avec succès')
            ->success()
            ->body('Numéro : ' . $card->card_number)
            ->send();
    }
}
