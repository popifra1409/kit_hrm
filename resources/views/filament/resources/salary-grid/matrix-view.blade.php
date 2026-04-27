<x-filament-panels::page>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Grille Salariale - Vue d'Ensemble
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Catégories (lignes) × Échelons (colonnes)
            </p>
        </div>
        <div class="flex gap-2">
            <x-filament::badge color="success">
                {{ $grids->flatten()->count() }} grilles actives
            </x-filament::badge>
        </div>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="sticky left-0 z-10 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-100 dark:bg-gray-800">
                        Cat \ Éch
                    </th>
                    @foreach($echelons as $echelon)
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        E{{ $echelon }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                @foreach($categories as $category)
                <tr class="group hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors duration-150">
                    <td class="sticky left-0 z-10 px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-400 dark:group-hover:text-white bg-gray-100 dark:bg-gray-800 group-hover:bg-gray-200 dark:group-hover:bg-gray-700 transition-colors duration-150">
                        C{{ $category }}
                    </td>
                    @foreach($echelons as $echelon)
                    @php
                    $grid = $grids->get($category)?->firstWhere('echelon', $echelon);
                    @endphp
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center transition-all duration-150
                                {{ $grid 
                                    ? 'bg-green-50 dark:bg-green-900/20 group-hover:bg-green-100 dark:group-hover:bg-green-900/40' 
                                    : 'bg-gray-50 dark:bg-gray-800/50 group-hover:bg-gray-100 dark:group-hover:bg-gray-700/50' 
                                }}">
                        @if($grid)
                        <div class="flex flex-col">
                            <span class="font-bold text-green-700 dark:text-green-500 dark:group-hover:text-green-400 transition-colors duration-150">
                                {{ number_format($grid->base_salary, 0, ',', ' ') }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400 transition-colors duration-150">
                                {{ number_format($grid->base_salary / 1000, 0) }}K FCFA
                            </span>
                        </div>
                        @else
                        <span class="text-gray-400 dark:text-gray-600 dark:group-hover:text-gray-500 transition-colors duration-150">-</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    💡 Légende
                </p>
                <ul class="text-sm text-blue-700 dark:text-blue-300 mt-2 space-y-1">
                    <li class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded"></span>
                        <span>Grille salariale active</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded"></span>
                        <span>Pas de grille définie</span>
                    </li>
                    <li class="mt-2 text-xs italic">
                        ✨ Survolez une ligne pour mettre en évidence les valeurs
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Grilles Actives</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $grids->flatten()->count() }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Salaire Min</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($grids->flatten()->min('base_salary') ?? 0, 0, ',', ' ') }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Salaire Max</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($grids->flatten()->max('base_salary') ?? 0, 0, ',', ' ') }}
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Salaire Moyen</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($grids->flatten()->avg('base_salary') ?? 0, 0, ',', ' ') }}
                    </p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>