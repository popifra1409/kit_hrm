<x-filament-panels::page>
    <div class="space-y-4" x-data="{ 
        openEmployees: {}, 
        searchQuery: '',
        filterEmployees(employees) {
            if (!this.searchQuery) return employees;
            const query = this.searchQuery.toLowerCase();
            return employees.filter(emp => {
                return emp.name.toLowerCase().includes(query) || 
                       emp.matricule.toLowerCase().includes(query) ||
                       emp.dependents.some(dep => dep.name.toLowerCase().includes(query));
            });
        }
    }">
        @php
        $employees = $this->getEmployeesWithDependents();
        $employeesData = $employees->map(fn($emp) => [
        'id' => $emp->id,
        'name' => $emp->full_name,
        'matricule' => $emp->matricule,
        'dependents' => $emp->dependents->map(fn($dep) => [
        'name' => $dep->full_name
        ])->toArray()
        ])->toArray();
        @endphp

        @if($employees->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Aucun ayant droit</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aucun employé n'a d'ayants droit enregistrés.</p>
        </div>
        @else
        {{-- Statistiques --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Employés</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $employees->count() }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Ayants Droit</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $employees->sum('dependents_count') }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Moyenne par Employé</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ number_format($employees->avg('dependents_count'), 1) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Cartes Actives</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $employees->flatMap->dependents->where('card_active', true)->count() }}
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Barre de Recherche --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 mb-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Rechercher un employé par nom, matricule ou ayant droit..."
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-150">
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                <span x-show="!searchQuery">💡 Tapez pour filtrer la liste en temps réel</span>
                <span x-show="searchQuery" x-text="'🔍 Recherche: ' + searchQuery"></span>
            </p>
        </div>

        {{-- Arbre des employés --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    👥 Liste des Employés et leurs Ayants Droit
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Cliquez sur <span class="inline-flex items-center justify-center w-5 h-5 bg-blue-100 dark:bg-blue-900/30 rounded text-blue-600 dark:text-blue-400 font-bold text-xs">+</span> pour déplier
                </p>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($employees as $employee)
                <div
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150"
                    x-show="!searchQuery || 
                                    '{{ strtolower($employee->full_name) }}'.includes(searchQuery.toLowerCase()) || 
                                    '{{ strtolower($employee->matricule) }}'.includes(searchQuery.toLowerCase()) ||
                                    {{ json_encode($employee->dependents->pluck('full_name')->map(fn($n) => strtolower($n))->toArray()) }}.some(name => name.includes(searchQuery.toLowerCase()))"
                    x-transition>
                    {{-- Ligne Employé --}}
                    <div
                        class="flex items-center p-4 cursor-pointer group"
                        @click="openEmployees[{{ $employee->id }}] = !openEmployees[{{ $employee->id }}]">
                        {{-- Icône +/- --}}
                        <div class="flex-shrink-0 mr-3">
                            <button
                                class="w-6 h-6 flex items-center justify-center rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors duration-150 font-bold text-sm"
                                x-show="!openEmployees[{{ $employee->id }}]">
                                +
                            </button>
                            <button
                                class="w-6 h-6 flex items-center justify-center rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors duration-150 font-bold text-sm"
                                x-show="openEmployees[{{ $employee->id }}]"
                                x-cloak>
                                −
                            </button>
                        </div>

                        {{-- Photo --}}
                        <div class="flex-shrink-0 mr-4">
                            @if($employee->photo_path)
                            <img src="{{ asset('storage/' . $employee->photo_path) }}" alt="{{ $employee->full_name }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                            <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center">
                                <span class="text-gray-600 dark:text-gray-400 font-bold text-sm">
                                    {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                                </span>
                            </div>
                            @endif
                        </div>

                        {{-- Informations --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $employee->full_name }}
                                </h4>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    {{ $employee->matricule }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                📧 {{ $employee->email ?? 'Pas d\'email' }} •
                                📞 {{ $employee->phone ?? 'Pas de téléphone' }}
                            </p>
                        </div>

                        {{-- Badge Nombre --}}
                        <div class="flex-shrink-0 ml-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                {{ $employee->dependents_count }} ayant(s) droit
                            </span>
                        </div>
                    </div>

                    {{-- Liste des Ayants Droit (Dépliable) --}}
                    <div
                        x-show="openEmployees[{{ $employee->id }}]"
                        x-collapse
                        class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="p-4 pl-16 space-y-3">
                            @foreach($employee->dependents as $dependent)
                            <div class="flex items-center p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-150">
                                {{-- Photo Ayant Droit --}}
                                <div class="flex-shrink-0 mr-3">
                                    @if($dependent->photo_path)
                                    <img src="{{ asset('storage/' . $dependent->photo_path) }}" alt="{{ $dependent->full_name }}" class="w-12 h-12 rounded-full object-cover">
                                    @else
                                    <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <span class="text-gray-500 dark:text-gray-400 text-xl">
                                            {{ $dependent->relationship === 'child' ? '👶' : ($dependent->relationship === 'spouse' ? '💑' : ($dependent->relationship === 'mother' ? '👩' : '👨')) }}
                                        </span>
                                    </div>
                                    @endif
                                </div>

                                {{-- Informations Ayant Droit --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $dependent->full_name }}
                                        </h5>
                                        <x-filament::badge color="{{ $dependent->relationship === 'spouse' ? 'danger' : ($dependent->relationship === 'child' ? 'success' : ($dependent->relationship === 'mother' ? 'warning' : 'info')) }}">
                                            {{ $dependent->getRelationshipLabel() }}
                                        </x-filament::badge>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $dependent->age }} ans
                                        </span>
                                        @if($dependent->is_active)
                                        <x-filament::badge color="success">
                                            ✓ Actif
                                        </x-filament::badge>
                                        @endif
                                        @if($dependent->card_active)
                                        <x-filament::badge color="primary">
                                            💳 Carte Active
                                        </x-filament::badge>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Taux de couverture: {{ $dependent->coverage_rate }}% •
                                        @if($dependent->card_number)
                                        Carte N°: {{ $dependent->card_number }}
                                        @else
                                        Pas de carte
                                        @endif
                                    </p>
                                </div>

                                {{-- Actions --}}
                                <div class="flex-shrink-0 ml-4 flex gap-2">
                                    <a href="{{ route('filament.admin.resources.dependents.edit', $dependent) }}"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors duration-150"
                                        title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @endpush
</x-filament-panels::page>