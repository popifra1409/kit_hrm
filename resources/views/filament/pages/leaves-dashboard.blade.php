<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $leavesByType = $this->getLeavesByType();
        $topEmployees = $this->getTopEmployeesByLeave();
        $lowBalance = $this->getEmployeesWithLowBalance();
        $pendingLevels = $this->getPendingApprovalsByLevel();
        $monthlyTrend = $this->getMonthlyTrend();
    @endphp

    {{-- Statistiques Principales --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">{{ $stats['pending'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Demandes en Attente</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ $stats['approved_this_month'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Approuvées ce Mois</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['on_leave'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Employés en Congé</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['total_days_year']) }}</div>
                <div class="text-sm text-gray-600 mt-1">Jours Approuvés {{ now()->year }}</div>
            </div>
        </x-filament::card>
    </div>

    {{-- Workflow d'Approbation --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">📋 Workflow d'Approbation</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                <div class="text-2xl font-bold text-yellow-700">{{ $pendingLevels['pending_n1'] }}</div>
                <div class="text-sm text-yellow-600 mt-1">En attente Niveau 1</div>
                <div class="text-xs text-gray-500">(Chef de Service)</div>
            </div>
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-700">{{ $pendingLevels['pending_n2'] }}</div>
                <div class="text-sm text-blue-600 mt-1">En attente Niveau 2</div>
                <div class="text-xs text-gray-500">(DRH)</div>
            </div>
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-700">{{ $pendingLevels['pending_final'] }}</div>
                <div class="text-sm text-green-600 mt-1">Approbation Finale</div>
                <div class="text-xs text-gray-500">(Direction)</div>
            </div>
        </div>
    </x-filament::card>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Congés par Type --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">📊 Congés par Type ({{ now()->year }})</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Approuvés</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">En attente
                            </th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Total Jours
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($leavesByType as $type)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium">{{ $type->name }}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                        {{ $type->approved_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                        {{ $type->pending_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center font-semibold">
                                    {{ number_format($type->total_days ?? 0) }} j
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-sm text-center text-gray-500">
                                    Aucune donnée disponible
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>

        {{-- Employés - Soldes Faibles --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">⚠️ Soldes Faibles (< 10 jours)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employé
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                        Disponible</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                        Utilisé</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($lowBalance as $balance)
                                    <tr>
                                        <td class="px-4 py-3 text-sm">{{ $balance->employee->full_name }}</td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full font-bold">
                                                {{ $balance->available }} j
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center text-gray-600">
                                            {{ $balance->used }} j
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-sm text-center text-gray-500">
                                            Aucun employé avec solde faible
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
        </x-filament::card>
    </div>

    {{-- Top 10 Employés par Jours de Congé --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">🏆 Top 10 - Employés par Jours de Congé ({{ now()->year }})</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Total Jours</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($topEmployees as $index => $employee)
                        <tr class="{{ $index < 3 ? 'bg-yellow-50' : '' }}">
                            <td class="px-4 py-3 text-sm font-bold">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 text-sm">{{ $employee->full_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $employee->currentService?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-center font-semibold">
                                {{ number_format($employee->total_leave_days ?? 0) }} jours
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-sm text-center text-gray-500">
                                Aucune donnée disponible
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::card>

    {{-- Tendance Mensuelle --}}
    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">📈 Tendance Mensuelle - Congés Approuvés ({{ now()->year }})</h3>
        <div class="overflow-x-auto">
            <div class="flex gap-2 min-w-max">
                @foreach ($monthlyTrend as $month)
                    <div class="flex-1 min-w-[80px]">
                        <div class="text-center">
                            <div class="h-40 flex items-end justify-center">
                                <div class="w-full bg-blue-500 rounded-t"
                                    style="height: {{ $month['count'] > 0 ? ($month['count'] / max(array_column($monthlyTrend, 'count'))) * 100 : 0 }}%">
                                </div>
                            </div>
                            <div class="text-lg font-bold text-blue-600 mt-2">{{ $month['count'] }}</div>
                            <div class="text-xs text-gray-600">{{ $month['month_fr'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
