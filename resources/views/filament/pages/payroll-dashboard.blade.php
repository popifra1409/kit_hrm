<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $statusBreakdown = $this->getPayrollByStatus();
        $monthlyTrend = $this->getMonthlyTrend();
        $topSalaries = $this->getTopSalaries();
        $byDepartment = $this->getPayrollByDepartment();
        $cotisations = $this->getCotisationsBreakdown();
    @endphp

    {{-- Période --}}
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-gray-800">{{ now()->format('F Y') }}</h2>
    </div>

    {{-- Statistiques Principales --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['current_month'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Bulletins Générés</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ $stats['validated_month'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Bulletins Validés</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">
                    {{ number_format($stats['gross_payroll'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600 mt-1">Masse Salariale Brute (FCFA)</div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">{{ number_format($stats['net_payroll'], 0, ',', ' ') }}
                </div>
                <div class="text-sm text-gray-600 mt-1">Masse Salariale Nette (FCFA)</div>
            </div>
        </x-filament::card>
    </div>

    {{-- Répartition par Statut --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">📊 Répartition par Statut</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
                <div class="text-2xl font-bold text-gray-700">{{ $statusBreakdown['draft'] }}</div>
                <div class="text-sm text-gray-600 mt-1">Brouillon</div>
            </div>
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-700">{{ $statusBreakdown['validated'] }}</div>
                <div class="text-sm text-green-600 mt-1">Validé</div>
            </div>
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-700">{{ $statusBreakdown['paid'] }}</div>
                <div class="text-sm text-blue-600 mt-1">Payé</div>
            </div>
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-center">
                <div class="text-2xl font-bold text-red-700">{{ $statusBreakdown['cancelled'] }}</div>
                <div class="text-sm text-red-600 mt-1">Annulé</div>
            </div>
        </div>
    </x-filament::card>

    {{-- Cotisations et Impôts --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">💼 Cotisations et Impôts du Mois</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="text-center">
                <div class="text-xl font-bold text-blue-600">
                    {{ number_format($cotisations['cnps_employee'], 0, ',', ' ') }}</div>
                <div class="text-xs text-gray-600 mt-1">CNPS Employé (4.2%)</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-indigo-600">
                    {{ number_format($cotisations['cnps_employer'], 0, ',', ' ') }}</div>
                <div class="text-xs text-gray-600 mt-1">CNPS Employeur (11.2%)</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-purple-600">
                    {{ number_format($cotisations['cnps_total'], 0, ',', ' ') }}</div>
                <div class="text-xs text-gray-600 mt-1">CNPS Total</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-orange-600">{{ number_format($cotisations['irpp'], 0, ',', ' ') }}
                </div>
                <div class="text-xs text-gray-600 mt-1">IRPP</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-red-600">{{ number_format($cotisations['cac'], 0, ',', ' ') }}</div>
                <div class="text-xs text-gray-600 mt-1">CAC (10% IRPP)</div>
            </div>
        </div>
    </x-filament::card>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Top 10 Salaires --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">🏆 Top 10 Salaires du Mois</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topSalaries as $index => $payroll)
                            <tr class="{{ $index < 3 ? 'bg-yellow-50' : '' }}">
                                <td class="px-4 py-3 text-sm font-bold">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm">{{ $payroll->employee->full_name }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">
                                    {{ number_format($payroll->net_salary, 0, ',', ' ') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm text-center text-gray-500">
                                    Aucun bulletin généré
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>

        {{-- Masse Salariale par Département --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">🏢 Masse Salariale par Département</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Département</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Employés</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Net</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($byDepartment as $dept => $data)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $dept }}</td>
                                <td class="px-4 py-3 text-sm text-center">{{ $data['count'] }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">
                                    {{ number_format($data['net'], 0, ',', ' ') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm text-center text-gray-500">
                                    Aucune donnée
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>

    {{-- Tendance Mensuelle --}}
    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">📈 Évolution Annuelle de la Masse Salariale ({{ now()->year }})</h3>
        <div class="overflow-x-auto">
            <div class="flex gap-2 min-w-max">
                @php
                    $maxGross = max(array_column($monthlyTrend, 'gross')) ?: 1;
                @endphp

                @foreach ($monthlyTrend as $month)
                    <div class="flex-1 min-w-[80px]">
                        <div class="text-center">
                            <div class="h-40 flex items-end justify-center gap-1">
                                <div class="w-1/2 bg-purple-500 rounded-t"
                                    style="height: {{ $month['gross'] > 0 ? ($month['gross'] / $maxGross) * 100 : 0 }}%"
                                    title="Brut: {{ number_format($month['gross'], 0, ',', ' ') }}">
                                </div>
                                <div class="w-1/2 bg-orange-500 rounded-t"
                                    style="height: {{ $month['net'] > 0 ? ($month['net'] / $maxGross) * 100 : 0 }}%"
                                    title="Net: {{ number_format($month['net'], 0, ',', ' ') }}">
                                </div>
                            </div>
                            <div class="text-xs font-bold text-gray-700 mt-2">{{ $month['count'] }}</div>
                            <div class="text-xs text-gray-600">{{ substr($month['month_name'], 0, 3) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-purple-500 rounded"></div>
                    <span class="text-xs text-gray-600">Brut</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-orange-500 rounded"></div>
                    <span class="text-xs text-gray-600">Net</span>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
