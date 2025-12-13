<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $byStatus = $this->getProcurementsByStatus();
        $byType = $this->getProcurementsByType();
        $byProcedure = $this->getProcurementsByProcedure();
        $recentProcurements = $this->getRecentProcurements();
        $upcomingDeadlines = $this->getUpcomingDeadlines();
        $topSuppliers = $this->getTopSuppliers();
        $monthlyTrend = $this->getMonthlyTrend();
        $contractExecution = $this->getContractExecutionStatus();
        $armpStatus = $this->getARMPStatus();
    @endphp

    {{-- Statistiques Principales --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['total_procurements'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Total Marchés</div>
                <div class="text-xs text-gray-500 mt-1">{{ $stats['active_procurements'] }} actifs</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">
                    {{ number_format($stats['total_estimated'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600 mt-1">Montant Estimé (FCFA)</div>
                <div class="text-xs text-gray-500 mt-1">Tous les marchés</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">
                    {{ number_format($stats['total_awarded'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600 mt-1">Montant Attribué (FCFA)</div>
                <div class="text-xs text-gray-500 mt-1">{{ $stats['awarded_procurements'] }} marchés</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ $stats['total_contracts_count'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Contrats</div>
                <div class="text-xs text-gray-500 mt-1">{{ $stats['active_contracts'] }} en cours</div>
            </div>
        </x-filament::card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        {{-- Répartition par Statut --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">📊 Marchés par Statut</h3>
            <div class="space-y-2">
                @foreach ($byStatus as $status => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-sm">
                            @switch($status)
                                @case('draft')
                                    📝 Brouillon
                                @break

                                @case('pending_approval')
                                    ⏳ En Approbation
                                @break

                                @case('approved')
                                    ✅ Approuvé
                                @break

                                @case('published')
                                    📢 Publié
                                @break

                                @case('bids_received')
                                    📬 Offres Reçues
                                @break

                                @case('evaluation')
                                    📋 En Évaluation
                                @break

                                @case('awarded')
                                    🏆 Attribué
                                @break

                                @case('contract_signed')
                                    ✍️ Contrat Signé
                                @break

                                @case('cancelled')
                                    ❌ Annulé
                                @break

                                @default
                                    {{ $status }}
                            @endswitch
                        </span>
                        <span class="text-sm font-bold">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::card>

        {{-- Répartition par Type --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">🏗️ Par Type de Marché</h3>
            <div class="space-y-3">
                @foreach ($byType as $type => $data)
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-semibold">{{ $type }}</span>
                            <span class="text-sm font-bold">{{ $data['count'] }}</span>
                        </div>
                        <div class="text-xs text-gray-600">
                            Estimé: {{ number_format($data['estimated'], 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::card>

        {{-- Statut ARMP --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">🏛️ Statut ARMP</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center p-2 bg-yellow-50 rounded">
                    <span class="text-sm">⏳ En Attente</span>
                    <span class="text-sm font-bold text-yellow-700">{{ $armpStatus['pending'] }}</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-green-50 rounded">
                    <span class="text-sm">✅ Approuvé</span>
                    <span class="text-sm font-bold text-green-700">{{ $armpStatus['approved'] }}</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-red-50 rounded">
                    <span class="text-sm">❌ Rejeté</span>
                    <span class="text-sm font-bold text-red-700">{{ $armpStatus['rejected'] }}</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="text-sm">➖ Non Requis</span>
                    <span class="text-sm font-bold text-gray-700">{{ $armpStatus['not_required'] }}</span>
                </div>
            </div>
        </x-filament::card>
    </div>

    {{-- Évolution Mensuelle --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">📈 Évolution Mensuelle {{ now()->year }}</h3>
        <div class="overflow-x-auto">
            <div class="flex gap-2 min-w-max">
                @php
                    $maxCount = max(array_column($monthlyTrend, 'count')) ?: 1;
                @endphp

                @foreach ($monthlyTrend as $month)
                    <div class="flex-1 min-w-[70px]">
                        <div class="text-center">
                            <div class="h-40 flex items-end justify-center">
                                <div class="w-full bg-blue-500 rounded-t"
                                    style="height: {{ $month['count'] > 0 ? ($month['count'] / $maxCount) * 100 : 0 }}%"
                                    title="Marchés: {{ $month['count'] }}">
                                </div>
                            </div>
                            <div class="text-xs font-bold text-gray-700 mt-2">{{ $month['count'] }}</div>
                            <div class="text-xs text-gray-600">{{ $month['month_name'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::card>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Échéances Prochaines --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">⏰ Échéances Prochaines (30 jours)</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Marché</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Échéance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($upcomingDeadlines as $procurement)
                            <tr>
                                <td class="px-3 py-2 text-sm">
                                    <div class="font-semibold">{{ $procurement->reference }}</div>
                                    <div class="text-xs text-gray-600">{{ Str::limit($procurement->title, 40) }}</div>
                                </td>
                                <td class="px-3 py-2 text-sm text-right">
                                    <div class="font-semibold">{{ $procurement->deadline_submission->format('d/m/Y') }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        {{ $procurement->deadline_submission->diffForHumans() }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-3 py-4 text-sm text-center text-gray-500">
                                    Aucune échéance prochaine
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>

        {{-- Top Fournisseurs --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">🏆 Top 10 Fournisseurs</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fournisseur</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Contrats</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topSuppliers as $index => $item)
                            <tr class="{{ $index < 3 ? 'bg-yellow-50' : '' }}">
                                <td class="px-3 py-2 text-sm">
                                    @if ($index < 3)
                                        <span class="mr-1">{{ ['🥇', '🥈', '🥉'][$index] }}</span>
                                    @endif
                                    {{ $item['supplier']->name }}
                                </td>
                                <td class="px-3 py-2 text-sm text-center font-semibold">{{ $item['count'] }}</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold">
                                    {{ number_format($item['total_amount'], 0, ',', ' ') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-sm text-center text-gray-500">
                                    Aucun fournisseur
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>

    {{-- Marchés Récents --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">🆕 Marchés Récents</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Objet</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentProcurements as $procurement)
                        <tr>
                            <td class="px-3 py-2 text-sm font-semibold">{{ $procurement->reference }}</td>
                            <td class="px-3 py-2 text-sm">{{ Str::limit($procurement->title, 50) }}</td>
                            <td class="px-3 py-2 text-sm">{{ $procurement->procurementType->name }}</td>
                            <td class="px-3 py-2 text-sm text-right">
                                {{ number_format($procurement->estimated_amount, 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-center">
                                <span
                                    class="px-2 py-1 text-xs rounded-full
                                @if ($procurement->status === 'published') bg-blue-100 text-blue-800
                                @elseif($procurement->status === 'awarded') bg-green-100 text-green-800
                                @elseif($procurement->status === 'draft') bg-gray-100 text-gray-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($procurement->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm">{{ $procurement->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-sm text-center text-gray-500">
                                Aucun marché récent
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::card>

    {{-- Exécution des Contrats --}}
    @if ($contractExecution->isNotEmpty())
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">📊 Exécution des Contrats en Cours</h3>
            <div class="space-y-4">
                @foreach ($contractExecution as $item)
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-semibold">{{ $item['contract']->contract_number }}</div>
                                <div class="text-sm text-gray-600">{{ $item['contract']->supplier->name }}</div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="text-lg font-bold {{ $item['is_delayed'] ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($item['progress'], 1) }}%
                                </div>
                                @if ($item['is_delayed'])
                                    <div class="text-xs text-red-600">Retard: {{ $item['delay_days'] }} jours</div>
                                @endif
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full {{ $item['is_delayed'] ? 'bg-red-600' : 'bg-green-600' }}"
                                style="width: {{ $item['progress'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::card>
    @endif
</x-filament-panels::page>
