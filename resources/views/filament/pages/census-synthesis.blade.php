<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ $stats['total'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Agents actifs suivis</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-red-600">{{ $stats['incomplete'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Fiches avec champ manquant</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">{{ $stats['never_submitted'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Jamais soumis de recensement</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ $stats['validated'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Recensements validés</div>
            </div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <h3 class="text-lg font-medium mb-1">État de toutes les fiches agents</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Tri des fiches les plus incomplètes en premier. Le filtre « Champ manquant » liste les
            mêmes champs que ceux proposés à l'agent lors d'un rejet ; « Aucun champ manquant » garde
            les dossiers entièrement renseignés.
        </p>

        {{ $this->table }}
    </x-filament::card>
</x-filament-panels::page>
