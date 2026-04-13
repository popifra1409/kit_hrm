<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['count'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Bulletins à Exporter</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['gross'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600 mt-1">Total Brut (FCFA)</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ number_format($stats['net'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600 mt-1">Total Net (FCFA)</div>
            </div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">Exporter les Bulletins de Paie</h3>

        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">ℹ️ Information :</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li>L'export comprend tous les bulletins du mois sélectionné</li>
                <li>Colonnes : Matricule, Nom, Qualification, Salaires, Cotisations, Net à Payer</li>
                <li>Format Excel (XLSX) recommandé pour l'exploitation des données</li>
                <li>Format CSV pour l'import dans d'autres logiciels</li>
            </ul>
        </div>

        <form wire:submit.prevent="export">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" color="success">
                    Télécharger l'export
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    <x-filament::card class="mt-6">
        <h3 class="text-lg font-medium mb-2">📋 Colonnes Exportées</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm text-gray-700">
            <div>✓ Matricule</div>
            <div>✓ Nom Complet</div>
            <div>✓ Qualification</div>
            <div>✓ Catégorie/Échelon</div>
            <div>✓ Service</div>
            <div>✓ Mois/Année</div>
            <div>✓ Salaire de Base</div>
            <div>✓ Salaire Imposable</div>
            <div>✓ Salaire Cotisable</div>
            <div>✓ Salaire Brut</div>
            <div>✓ CNPS Employé</div>
            <div>✓ CNPS Employeur</div>
            <div>✓ IRPP</div>
            <div>✓ CAC</div>
            <div>✓ Total Retenues</div>
            <div>✓ Net à Payer</div>
            <div>✓ Statut</div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
