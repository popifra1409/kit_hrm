<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-red-600">{{ $stats['very_urgent'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Très Urgent (< 3 mois)</div>
                </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">{{ $stats['urgent'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Urgent (3-6 mois)</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['to_plan'] }}</div>
                <div class="text-sm text-gray-600 mt-1">À planifier (6-12 mois)</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-800">{{ $stats['total'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Total dans l'année</div>
            </div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">Liste des Employés Proches de la Retraite</h3>

        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">📋 Actions recommandées :</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li><strong>Très Urgent (&lt; 3 mois)</strong> : Préparer le dossier de retraite immédiatement</li>
                <li><strong>Urgent (3-6 mois)</strong> : Planifier le recrutement de remplacement</li>
                <li><strong>À planifier (6-12 mois)</strong> : Anticiper la transmission des compétences</li>
            </ul>
        </div>

        {{ $this->table }}
    </x-filament::card>
</x-filament-panels::page>
