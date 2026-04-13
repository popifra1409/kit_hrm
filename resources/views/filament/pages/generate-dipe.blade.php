<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['count'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Bulletins à Télédéclarer</div>
            </div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">📄 Génération du DIPE CNPS</h3>

        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">ℹ️ Information DIPE :</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li><strong>DIPE Mensuel</strong> : À produire chaque mois pour télédéclaration CNPS</li>
                <li><strong>DIPE Début d'Exercice</strong> : À produire en début d'année</li>
                <li><strong>DIPE Fin d'Exercice</strong> : À produire en fin d'année pour régularisations</li>
                <li>Format : Fichier texte (.txt) conforme aux spécifications CNPS</li>
                <li>Obligatoire pour les entreprises de 50+ employés</li>
            </ul>
        </div>

        <form wire:submit.prevent="generate">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" color="success">
                    Générer le DIPE
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    <x-filament::card class="mt-6">
        <h3 class="text-lg font-medium mb-2">📋 Structure du DIPE Mensuel</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Champ</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 uppercase">Longueur</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-3 py-2">Code Enregistrement</td>
                        <td class="px-3 py-2 text-center">3</td>
                        <td class="px-3 py-2">C04 (fixe)</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2">Numéro DIPE</td>
                        <td class="px-3 py-2 text-center">5</td>
                        <td class="px-3 py-2">Numéro séquentiel</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Numéro Contribuable</td>
                        <td class="px-3 py-2 text-center">14</td>
                        <td class="px-3 py-2">N° contribuable CHUY</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2">Numéro Employeur</td>
                        <td class="px-3 py-2 text-center">10</td>
                        <td class="px-3 py-2">N° CNPS employeur</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Numéro Assuré</td>
                        <td class="px-3 py-2 text-center">10</td>
                        <td class="px-3 py-2">N° CNPS employé</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2">Salaire Brut</td>
                        <td class="px-3 py-2 text-center">10</td>
                        <td class="px-3 py-2">Salaire brut mensuel</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Salaire Taxable</td>
                        <td class="px-3 py-2 text-center">10</td>
                        <td class="px-3 py-2">Base IRPP</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2">Salaire Cotisable</td>
                        <td class="px-3 py-2 text-center">10</td>
                        <td class="px-3 py-2">Base CNPS</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">IRPP</td>
                        <td class="px-3 py-2 text-center">8</td>
                        <td class="px-3 py-2">Impôt retenu</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-3 py-2">Taxe Communale</td>
                        <td class="px-3 py-2 text-center">6</td>
                        <td class="px-3 py-2">Taxe développement local</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3 text-sm text-gray-600">
            <strong>Total par ligne :</strong> 135 caractères (format fixe)
        </div>
    </x-filament::card>

    <x-filament::card class="mt-6">
        <h3 class="text-lg font-medium mb-2">⚙️ Paramètres CHUY</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-semibold">Numéro Employeur CNPS :</span>
                <span class="text-gray-700">{{ App\Filament\Pages\GenerateDIPE::NUMERO_EMPLOYEUR }}</span>
            </div>
            <div>
                <span class="font-semibold">Numéro Contribuable :</span>
                <span class="text-gray-700">{{ App\Filament\Pages\GenerateDIPE::NUMERO_CONTRIBUABLE }}</span>
            </div>
            <div>
                <span class="font-semibold">Régime CNPS :</span>
                <span class="text-gray-700">Régime {{ App\Filament\Pages\GenerateDIPE::REGIME_CNPS }} (Général)</span>
            </div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
