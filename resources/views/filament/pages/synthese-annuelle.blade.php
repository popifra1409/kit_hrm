<x-filament-panels::page>
    @php
        $data = $this->getAnnualSummary();
        $byEmployee = $data['by_employee'];
        $globalSummary = $data['global_summary'];
        $monthlyBreakdown = $data['monthly_breakdown'];
    @endphp

    <div class="mb-4">
        <form wire:submit.prevent="$refresh">
            {{ $this->form }}
            <div class="mt-4 flex gap-2">
                <x-filament::button type="submit">
                    Actualiser
                </x-filament::button>
                <x-filament::button wire:click="exportExcel" color="success">
                    Exporter Excel Global
                </x-filament::button>
            </div>
        </form>
    </div>

    {{-- Synthèse Globale --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">📊 Synthèse Globale Année {{ $this->year }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-700">{{ $globalSummary['total_employees'] }}</div>
                <div class="text-sm text-gray-600">Employés</div>
            </div>
            <div class="p-4 bg-purple-50 rounded-lg">
                <div class="text-lg font-bold text-purple-700">
                    {{ number_format($globalSummary['total_gross_salary'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600">Brut Total</div>
            </div>
            <div class="p-4 bg-indigo-50 rounded-lg">
                <div class="text-lg font-bold text-indigo-700">
                    {{ number_format($globalSummary['total_cnps_total'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600">CNPS Total</div>
            </div>
            <div class="p-4 bg-orange-50 rounded-lg">
                <div class="text-lg font-bold text-orange-700">
                    {{ number_format($globalSummary['total_irpp'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600">IRPP Total</div>
            </div>
            <div class="p-4 bg-green-50 rounded-lg">
                <div class="text-lg font-bold text-green-700">
                    {{ number_format($globalSummary['total_net_salary'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600">Net Total</div>
            </div>
        </div>
    </x-filament::card>

    {{-- Répartition Mensuelle --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">📅 Répartition Mensuelle</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mois</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Effectif</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Brut</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">CNPS</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">IRPP</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($monthlyBreakdown as $month => $data)
                        <tr class="{{ $data['count'] > 0 ? '' : 'text-gray-400' }}">
                            <td class="px-3 py-2 text-sm">{{ $data['month_name'] }}</td>
                            <td class="px-3 py-2 text-sm text-center">{{ $data['count'] }}</td>
                            <td class="px-3 py-2 text-sm text-right">{{ number_format($data['gross'], 0, ',', ' ') }}
                            </td>
                            <td class="px-3 py-2 text-sm text-right">
                                {{ number_format($data['cnps_employee'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-right">{{ number_format($data['irpp'], 0, ',', ' ') }}
                            </td>
                            <td class="px-3 py-2 text-sm text-right font-semibold">
                                {{ number_format($data['net'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-100 font-bold">
                        <td class="px-3 py-2 text-sm">TOTAL ANNUEL</td>
                        <td class="px-3 py-2 text-sm text-center">-</td>
                        <td class="px-3 py-2 text-sm text-right">
                            {{ number_format($globalSummary['total_gross_salary'], 0, ',', ' ') }}</td>
                        <td class="px-3 py-2 text-sm text-right">
                            {{ number_format($globalSummary['total_cnps_employee'], 0, ',', ' ') }}</td>
                        <td class="px-3 py-2 text-sm text-right">
                            {{ number_format($globalSummary['total_irpp'], 0, ',', ' ') }}</td>
                        <td class="px-3 py-2 text-sm text-right">
                            {{ number_format($globalSummary['total_net_salary'], 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::card>

    {{-- Détail Par Employé --}}
    <x-filament::card>
        <h3 class="text-lg font-medium mb-4">👥 Détail par Employé ({{ $byEmployee->count() }} employés)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Matricule</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">N° CNPS</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Mois Payés</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Brut Total</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">CNPS</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">IRPP</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">CAC</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Retenues</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net Total</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($byEmployee as $emp)
                        <tr>
                            <td class="px-3 py-2 text-sm">{{ $emp['matricule'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ $emp['full_name'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ $emp['cnps_number'] ?? 'N/A' }}</td>
                            <td class="px-3 py-2 text-sm text-center">{{ $emp['months_paid'] }}</td>
                            <td class="px-3 py-2 text-sm text-right">
                                {{ number_format($emp['total_gross_salary'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-right">
                                {{ number_format($emp['total_cnps_employee'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-right">
                                {{ number_format($emp['total_irpp'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-right">
                                {{ number_format($emp['total_cac'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-right">
                                {{ number_format($emp['total_deductions'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-right font-semibold">
                                {{ number_format($emp['total_net_salary'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-sm text-center">
                                <button wire:click="exportEmployeeDetail({{ $emp['employee']->id }})"
                                    class="text-blue-600 hover:text-blue-900" title="Exporter détail">
                                    📥
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-4 text-sm text-center text-gray-500">
                                Aucune donnée disponible
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::card>

    @if ($this->employee_id)
        @php
            $selectedEmployee = $byEmployee->firstWhere('employee.id', $this->employee_id);
        @endphp

        @if ($selectedEmployee)
            <x-filament::card class="mt-6">
                <h3 class="text-lg font-medium mb-4">📋 Détail Mensuel - {{ $selectedEmployee['full_name'] }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mois</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Brut</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">CNPS</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">IRPP</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">CAC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Retenues
                                </th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($selectedEmployee['monthly_detail'] as $payroll)
                                <tr>
                                    <td class="px-3 py-2 text-sm">{{ $payroll->month_name }}</td>
                                    <td class="px-3 py-2 text-sm text-right">
                                        {{ number_format($payroll->gross_salary, 0, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-sm text-right">
                                        {{ number_format($payroll->cnps_employee, 0, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-sm text-right">
                                        {{ number_format($payroll->irpp, 0, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-sm text-right">
                                        {{ number_format($payroll->cac, 0, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-sm text-right">
                                        {{ number_format($payroll->total_deductions, 0, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-semibold">
                                        {{ number_format($payroll->net_salary, 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        @endif
    @endif
</x-filament-panels::page>
