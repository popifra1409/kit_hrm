<x-filament-panels::page>
    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">Générer les Bulletins de Paie</h3>

        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">ℹ️ Information :</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li>Cette action génère automatiquement les bulletins de paie pour tous les employés actifs</li>
                <li>Le salaire de base est récupéré depuis la grille salariale (catégorie/échelon)</li>
                <li>Les primes, indemnités et retenues sont calculées automatiquement</li>
                <li>Si un bulletin existe déjà, il sera mis à jour</li>
            </ul>
        </div>

        <form wire:submit.prevent="generateAll">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" color="success">
                    Générer tous les bulletins
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
