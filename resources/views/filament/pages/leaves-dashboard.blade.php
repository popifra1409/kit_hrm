<x-filament-panels::page>
    {{-- Stats globales --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 mb-6">
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['pending'] }}</div>
            <div class="text-sm text-gray-500">Demandes en attente</div>
        </div>
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl font-bold text-success-600">{{ $stats['approved_this_month'] }}</div>
            <div class="text-sm text-gray-500">Approuvées ce mois</div>
        </div>
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl font-bold text-info-600">{{ $stats['on_leave'] }}</div>
            <div class="text-sm text-gray-500">En congé actuellement</div>
        </div>
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl font-bold">{{ $stats['total_days_year'] }}</div>
            <div class="text-sm text-gray-500">Jours pris cette année</div>
        </div>
    </div>

    {{-- Filtres --}}
    <x-filament::section heading="Filtres" class="mb-6">
        {{ $this->form }}
    </x-filament::section>

    {{-- Retard de retour --}}
    @if($overdueReturn->isNotEmpty())
        <x-filament::section heading="⚠️ Retour non enregistré (date de fin dépassée)" class="mb-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4">Employé</th>
                            <th class="py-2 pr-4">Service</th>
                            <th class="py-2 pr-4">Type</th>
                            <th class="py-2 pr-4">Fin prévue</th>
                            <th class="py-2 pr-4">Retard</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overdueReturn as $leave)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-4 font-medium">{{ $leave->employee->full_name }} <span class="text-gray-400">({{ $leave->employee->matricule }})</span></td>
                                <td class="py-2 pr-4">{{ $leave->employee->currentService?->name ?? $leave->employee->department?->name ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $leave->leaveType?->name }}</td>
                                <td class="py-2 pr-4">{{ $leave->end_date->format('d/m/Y') }}</td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full bg-danger-100 px-2 py-0.5 text-xs font-medium text-danger-700">
                                        {{ $leave->end_date->diffInDays(now()) }} jour(s)
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Demandes en attente par étape --}}
    <x-filament::section heading="Demandes en Attente par Étape du Circuit" class="mb-6">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
            @foreach($pendingByStep as $item)
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 text-center">
                    <div class="text-xl font-bold">{{ $item['count'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $item['step']->name }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Actuellement en congé --}}
    <x-filament::section heading="Actuellement en Congé" class="mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">Employé</th>
                        <th class="py-2 pr-4">Service</th>
                        <th class="py-2 pr-4">Type</th>
                        <th class="py-2 pr-4">Départ</th>
                        <th class="py-2 pr-4">Retour prévu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($currentlyOnLeave as $leave)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-4 font-medium">{{ $leave->employee->full_name }} <span class="text-gray-400">({{ $leave->employee->matricule }})</span></td>
                            <td class="py-2 pr-4">{{ $leave->employee->currentService?->name ?? $leave->employee->department?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $leave->leaveType?->name }}</td>
                            <td class="py-2 pr-4">{{ $leave->start_date->format('d/m/Y') }}</td>
                            <td class="py-2 pr-4 font-semibold">{{ $leave->end_date->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-500">Personne en congé actuellement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Répartition par type --}}
        <x-filament::section heading="Répartition par Type (cette année)">
            <div class="space-y-2">
                @foreach($leavesByType as $type)
                    <div class="flex justify-between text-sm border-b border-gray-100 dark:border-gray-800 py-1.5">
                        <span>{{ $type->name }}</span>
                        <span class="text-gray-500">{{ $type->approved_count }} approuvés · {{ $type->pending_count }} en attente · {{ $type->total_days ?? 0 }}j</span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Top employés --}}
        <x-filament::section heading="Top 10 — Jours de Congé Pris (cette année)">
            <div class="space-y-2">
                @forelse($topEmployees as $employee)
                    @if($employee->total_leave_days)
                        <div class="flex justify-between text-sm border-b border-gray-100 dark:border-gray-800 py-1.5">
                            <span>{{ $employee->full_name }}</span>
                            <span class="font-semibold">{{ $employee->total_leave_days }}j</span>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-500 text-sm">Aucune donnée.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>

    {{-- Éligibles à partir en congé --}}
    <x-filament::section heading="Éligibles à Partir en Congé (solde disponible)" class="mb-6">
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white dark:bg-gray-900">
                    <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">Employé</th>
                        <th class="py-2 pr-4">Service</th>
                        <th class="py-2 pr-4">Droit</th>
                        <th class="py-2 pr-4">Disponible</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eligible as $item)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-4 font-medium">{{ $item['employee']->full_name }} <span class="text-gray-400">({{ $item['employee']->matricule }})</span></td>
                            <td class="py-2 pr-4">{{ $item['employee']->currentService?->name ?? $item['employee']->department?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $item['entitlement'] }} j</td>
                            <td class="py-2 pr-4">
                                <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700">
                                    {{ $item['available'] }} j
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-500">Aucun employé éligible avec solde disponible sur cette sélection.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Solde faible --}}
        <x-filament::section heading="Solde Faible">
            <div class="space-y-2">
                @forelse($lowBalance as $item)
                    <div class="flex justify-between text-sm border-b border-gray-100 dark:border-gray-800 py-1.5">
                        <span>{{ $item['employee']->full_name }}</span>
                        <span class="inline-flex items-center rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700">
                            {{ $item['available'] }} j restants
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Aucun employé sous ce seuil.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Sans congé depuis longtemps --}}
        <x-filament::section heading="Sans Congé Depuis {{ $yearsThreshold }} An(s) ou Plus">
            <div class="space-y-2 max-h-72 overflow-y-auto">
                @forelse($longAgo as $item)
                    <div class="flex justify-between text-sm border-b border-gray-100 dark:border-gray-800 py-1.5">
                        <span>{{ $item['employee']->full_name }}</span>
                        <span class="inline-flex items-center rounded-full bg-danger-100 px-2 py-0.5 text-xs font-medium text-danger-700">
                            {{ $item['years_ago'] !== null ? $item['years_ago'] . ' an(s)' : 'Jamais parti' }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Aucun employé concerné.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>