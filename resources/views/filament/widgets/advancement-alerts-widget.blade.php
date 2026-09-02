<x-filament-widgets::widget>
    @php
        $eligible = $this->getEligibleEmployees();
        $count = $eligible->count();
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                @if ($count > 0)
                    <span class="flex h-3 w-3 relative">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span>
                    </span>
                @else
                    <span class="text-green-500">✅</span>
                @endif

                <span class="text-lg font-semibold">
                    Alertes Avancements d'Échelon
                </span>

                @if ($count > 0)
                    <span
                        class="ml-2 inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-sm font-medium text-orange-800">
                        {{ $count }} employé{{ $count > 1 ? 's' : '' }} éligible{{ $count > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </x-slot>

        @if ($count > 0)
            {{-- Alerte --}}
            <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg mb-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-orange-800">
                            <strong>{{ $count }}</strong> employé{{ $count > 1 ? 's ont' : ' a' }} atteint la
                            durée minimale dans leur échelon actuel et
                            {{ $count > 1 ? 'sont éligibles' : 'est éligible' }} à un avancement d'échelon.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tableau --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Employé
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Échelon Actuel
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prochain Échelon
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ancienneté Échelon
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date Éligibilité
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($eligible as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item['employee']->full_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $item['employee']->matricule }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                                        Échelon {{ $item['current_echelon'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                        Échelon {{ $item['next_echelon'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-semibold">
                                        {{ $item['tenure_formatted'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        requis: {{ $item['required_months'] }} mois
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $item['eligible_date']->format('d/m/Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $item['eligible_date']->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap">
                                    <a href="{{ route('filament.admin.resources.employees.edit', $item['employee']) }}"
                                        class="text-blue-600 hover:text-blue-900 transition-colors">
                                        Voir détails →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            {{-- Pas d'alertes --}}
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800">
                            <strong>Aucune alerte d'avancement.</strong> Tous les employés sont à jour dans leurs
                            échelons.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>