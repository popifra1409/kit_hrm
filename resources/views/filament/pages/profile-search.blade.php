<x-filament-panels::page>
    <x-filament::section heading="Critères de Recherche" class="mb-6">
        {{ $this->form }}
    </x-filament::section>

    <x-filament::section heading="{{ $total }} employé(s) trouvé(s)">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($employees as $employee)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                            @if($employee->photo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($employee->photo) }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-sm font-bold text-gray-500">{{ mb_substr($employee->first_name ?? $employee->last_name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="font-semibold text-sm">{{ $employee->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $employee->matricule }}</div>
                        </div>
                    </div>

                    <div class="text-xs space-y-1 text-gray-600 dark:text-gray-300">
                        <div><strong>Corps de métier :</strong> {{ $employee->tradeBody?->name ?? '—' }}</div>
                        <div><strong>Qualification :</strong> {{ $employee->qualification?->name ?? '—' }}</div>
                        <div><strong>Poste :</strong> {{ $employee->jobTitle?->name ?? '—' }}</div>
                        <div><strong>Service :</strong> {{ $employee->currentService?->name ?? $employee->department?->name ?? '—' }}</div>
                        <div><strong>Ancienneté :</strong> {{ $employee->anciennete_formatted }}</div>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('filament.admin.resources.employees.view', $employee) }}"
                           class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 hover:bg-gray-200">
                            Voir la fiche
                        </a>
                        <a href="{{ route('employees.profile.preview', $employee) }}" target="_blank"
                           class="text-xs px-2 py-1 rounded bg-primary-100 text-primary-700 hover:bg-primary-200">
                            Profil Professionnel
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 col-span-full text-center py-6">Aucun employé ne correspond à ces critères.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>