<x-filament-panels::page>
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-blue-800">
                    Configuration de l'Établissement
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Cette page vous permet de configurer toutes les informations de votre établissement hospitalier.
                    </p>
                    <p class="mt-1">Ces informations seront utilisées dans les documents PDF (bulletins de paie,
                        décisions, contrats, etc.)</p>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" color="success">
                Enregistrer la Configuration
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
