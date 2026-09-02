<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex flex-wrap gap-2">
            <x-filament::badge color="primary" size="lg">
                {{ $totalEmployees }} employé(s) {{ $activeOnly ? 'actifs' : 'au total' }}
            </x-filament::badge>

            @if($unassigned > 0)
                <x-filament::badge color="danger" size="lg">
                    {{ $unassigned }} sans corps de métier
                </x-filament::badge>
            @endif

            @if($undefinedStatus > 0)
                <x-filament::badge color="warning" size="lg">
                    {{ $undefinedStatus }} sans statut administratif
                </x-filament::badge>
            @endif
        </div>

        <x-filament::button wire:click="toggleActiveOnly" color="gray" size="sm">
            {{ $activeOnly ? 'Afficher tous les employés' : 'Afficher les actifs uniquement' }}
        </x-filament::button>
    </div>

    {{-- Sélecteur de corps de métier --}}
    <x-filament::section heading="Filtrer les corps de métier affichés" class="mb-6">
        {{ $this->form }}
    </x-filament::section>

    {{-- Répartition par statut administratif --}}
    <x-filament::section heading="Répartition par Statut Administratif" class="mb-6">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach($byStatus as $status)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-2xl font-bold">{{ $status['count'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ $status['label'] }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $status['pct'] }}%</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Détail par corps de métier : tableau --}}
    <x-filament::section heading="Détail par Corps de Métier">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">Corps de Métier</th>
                        <th class="py-2 pr-4">Catégorie</th>
                        <th class="py-2 pr-4">Effectif</th>
                        <th class="py-2 pr-4 w-1/3">Répartition</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tradeBodies as $tb)
                        @php
                            $pct = $maxCount > 0 ? ($tb->employees_count / $maxCount) * 100 : 0;
                            $barColor = match($tb->category) {
                                'medical' => 'bg-success-500',
                                'technical' => 'bg-warning-500',
                                'administrative' => 'bg-info-500',
                                'support' => 'bg-gray-400',
                                default => 'bg-gray-300',
                            };
                            $categoryLabel = match($tb->category) {
                                'medical' => '🏥 Médical',
                                'technical' => '⚙️ Technique',
                                'administrative' => '📋 Administratif',
                                'support' => '🔧 Support',
                                default => '—',
                            };
                        @endphp
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-4 font-medium">{{ $tb->name }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $categoryLabel }}</td>
                            <td class="py-2 pr-4">{{ $tb->employees_count }}</td>
                            <td class="py-2 pr-4">
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5">
                                    <div class="{{ $barColor }} h-2.5 rounded-full" style="width: {{ max($pct, $tb->employees_count > 0 ? 2 : 0) }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-500">
                                Aucun corps de métier ne correspond à la sélection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>