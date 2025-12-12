<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['total'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Total Employés Actifs</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ $stats['with_balances'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Avec Soldes Initialisés</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">{{ $stats['without_balances'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Sans Soldes</div>
            </div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">Initialiser les Soldes de Congés</h3>

        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">ℹ️ Information :</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li><strong>Congé Annuel (CA)</strong> : 30 jours de base + 1 jour tous les 5 ans d'ancienneté (max +3
                    jours)</li>
                <li><strong>Autres types</strong> : Utilise la valeur par défaut du type de congé</li>
                <li>Si un solde existe déjà pour l'employé/année/type, il sera mis à jour</li>
                <li>Les jours utilisés et en attente seront préservés</li>
            </ul>
        </div>

        <form wire:submit.prevent="initializeAll">
            {{ $this->form }}

            <div class="mt-4 flex gap-2">
                <x-filament::button type="submit" color="success">
                    Initialiser tous les soldes
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    <x-filament::card class="mt-4">
        <h3 class="text-lg font-medium mb-2">Règles de calcul du Congé Annuel</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ancienneté</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jours de Base</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bonus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">0-4 ans</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">30 jours</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">0 jour</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">30 jours</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm">5-9 ans</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">30 jours</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">+1 jour</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">31 jours</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">10-14 ans</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">30 jours</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">+2 jours</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">32 jours</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm">15+ ans</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">30 jours</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">+3 jours</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">33 jours</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::card>
</x-filament-panels::page>
