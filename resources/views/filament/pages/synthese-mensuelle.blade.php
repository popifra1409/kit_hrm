<x-filament-panels::page>
    @php
        $data = $this->getSummaryData();
        $summary = $data['summary'];
        $byDepartment = $data['by_department'];
        $byPersonnelType = $data['by_personnel_type'];
    @endphp

    <div class="mb-4">
        <form wire:submit.prevent="$refresh">
            {{ $this->form }}
            <div class="mt-4 flex gap-2">
                <x-filament::button type="submit">
                    Actualiser
                </x-filament::button>
                <x-filament::button wire:click="exportExcel" color="success">
                    Exporter Excel
                </x-filament::button>
            </div>
        </form>
    </div>

    {{-- Synthèse Globale --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">📊 Synthèse Globale</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-700">{{ $summary['total_employees'] }}</div>
                <div class="text-sm text-gray-600">Employés</div>
            </div>
            <div class="p-4 bg-purple-50 rounded-lg">
                <div class="text-xl font-bold text-purple-700">
                    {{ number_format($summary['total_gross_salary'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600">Masse Salariale Brute</div>
            </div>
            <div class="p-4 bg-green-50 rounded-lg">
                <div class="text-xl font-bold text-green-700">
                    {{ number_format($summary['total_net_salary'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600">Masse Salariale Nette</div>
            </div>
            <div class="p-4 bg-red-50 rounded-lg">
                <div class="text-xl font-bold text-red-700">
                    {{ number_format($summary['total_deductions'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-600">Total Retenues</div>
            </div>
        </div>
    </x-filament::card>

    {{-- Cotisations et Impôts --}}
    <x-filament::card class="mb-6">
        <h3 class="text-lg font-medium mb-4">💼 Cotisations et Impôts</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Désignation</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-3 text-sm">CNPS Part Employé (4.2%)</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            {{ number_format($summary['total_cnps_employee'], 0, ',', ' ') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 text-sm">CNPS Part Employeur (11.2%)</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            {{ number_format($summary['total_cnps_employer'], 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-sm font-bold">CNPS Total</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-blue-700">
                            {{ number_format($summary['total_cnps_total'], 0, ',', ' ') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 text-sm">IRPP</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            {{ number_format($summary['total_irpp'], 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-sm">CAC (10% IRPP)</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            {{ number_format($summary['total_cac'], 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::card>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Par Département --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">🏢 Par Département</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Département</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Effectif</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($byDepartment as $dept => $data)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $dept }}</td>
                                <td class="px-3 py-2 text-sm text-center">{{ $data['count'] }}</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold">
                                    {{ number_format($data['net'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>

        {{-- Par Type Personnel --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">👥 Par Type de Personnel</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Effectif</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($byPersonnelType as $type => $data)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $type }}</td>
                                <td class="px-3 py-2 text-sm text-center">{{ $data['count'] }}</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold">
                                    {{ number_format($data['net'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
