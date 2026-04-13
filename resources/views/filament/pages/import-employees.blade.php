<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit.prevent="import">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit">
                    Importer les employés
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    <x-filament::card class="mt-4">
        <h3 class="text-lg font-medium mb-2">Instructions</h3>
        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600">
            <li>Le fichier doit être au format Excel (.xlsx ou .xls)</li>
            <li>Les colonnes attendues : Matricule, Nom, Prénom, Catégorie/Échelon, Qualification, etc.</li>
            <li>Les doublons (même matricule) seront ignorés</li>
            <li>L'import peut prendre quelques minutes pour 648 employés</li>
        </ul>
    </x-filament::card>
</x-filament-panels::page>
