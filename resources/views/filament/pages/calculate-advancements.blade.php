<x-filament-panels::page>
    <x-filament::card>
        <div class="mb-4">
            <h3 class="text-lg font-medium">Critères d'éligibilité</h3>
            <ul class="list-disc list-inside mt-2 text-sm text-gray-600">
                <li>Ancienneté minimum : <strong>2 ans</strong></li>
                <li>Dossier disciplinaire : <strong>0 point</strong> (aucune sanction)</li>
                <li>Statut : <strong>Actif</strong></li>
            </ul>
        </div>

        <div class="mb-4">
            <x-filament::button wire:click="generateAllAdvancements" color="success">
                Générer tous les avancements éligibles
            </x-filament::button>
        </div>
    </x-filament::card>

    <x-filament::card class="mt-4">
        <h3 class="text-lg font-medium mb-4">Employés Éligibles</h3>
        {{ $this->table }}
    </x-filament::card>
</x-filament-panels::page>
