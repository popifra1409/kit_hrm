<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->record->isRejected())
            <x-filament::section>
                <div class="text-danger-600 font-semibold">❌ Rejeté le {{ $this->record->rejected_at?->format('d/m/Y H:i') }}</div>
                <p class="text-sm text-gray-600 mt-1">{{ $this->record->rejection_reason }}</p>
            </x-filament::section>
        @elseif($this->record->isValidated())
            <x-filament::section>
                <div class="text-success-600 font-semibold">✅ Validé le {{ $this->record->validated_at?->format('d/m/Y H:i') }} par {{ $this->record->validatedBy?->name }}</div>
            </x-filament::section>
        @endif

        <x-filament::section heading="Informations Personnelles">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">Champ</th>
                        <th class="py-2 pr-4">Actuel</th>
                        <th class="py-2 pr-4">Soumis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['phone' => 'Téléphone', 'email' => 'Email', 'address' => 'Adresse', 'city' => 'Ville'] as $key => $label)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-4 font-medium">{{ $label }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $personalOld[$key] ?? '—' }}</td>
                            <td class="py-2 pr-4 {{ ($personalOld[$key] ?? null) !== ($personalNew[$key] ?? null) ? 'font-semibold text-primary-600' : '' }}">
                                {{ $personalNew[$key] ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Ayants Droit">
            @if($newDependents->isNotEmpty())
                <h4 class="text-sm font-semibold text-success-600 mb-2">➕ Nouveaux à créer ({{ $newDependents->count() }})</h4>
                <div class="space-y-2 mb-4">
                    @foreach($newDependents as $item)
                        <div class="p-3 rounded-lg bg-success-50 dark:bg-success-900/20 text-sm">
                            <strong>{{ $item['first_name'] ?? '' }} {{ $item['last_name'] }}</strong> — {{ $item['relationship'] }}, né(e) le {{ $item['birth_date'] }}
                        </div>
                    @endforeach
                </div>
            @endif

            @if($updatedDependents->isNotEmpty())
                <h4 class="text-sm font-semibold text-primary-600 mb-2">✏️ Mises à jour ({{ $updatedDependents->count() }})</h4>
                <div class="space-y-2 mb-4">
                    @foreach($updatedDependents as $pair)
                        <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20 text-sm">
                            <strong>{{ $pair['old']->full_name }}</strong>
                            <span class="text-gray-500">(actuel : {{ $pair['old']->phone ?? '—' }})</span>
                            → <span class="font-semibold">{{ $pair['new']['phone'] ?? '—' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($unaddressedDependents->isNotEmpty())
                <h4 class="text-sm font-semibold text-warning-600 mb-2">⚠️ Existants non repris dans la soumission ({{ $unaddressedDependents->count() }})</h4>
                <div class="space-y-2">
                    @foreach($unaddressedDependents as $dependent)
                        <div class="p-3 rounded-lg bg-warning-50 dark:bg-warning-900/20 text-sm flex items-center justify-between">
                            <span><strong>{{ $dependent->full_name }}</strong> — {{ $dependent->getRelationshipLabel() }}</span>
                            @if($this->record->isSubmitted())
                                <button wire:click="deactivateDependent({{ $dependent->id }})"
                                        wire:confirm="Désactiver cet ayant droit ?"
                                        class="text-xs px-2 py-1 rounded bg-warning-200 dark:bg-warning-800 hover:opacity-80">
                                    Désactiver
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if($newDependents->isEmpty() && $updatedDependents->isEmpty() && $unaddressedDependents->isEmpty())
                <p class="text-sm text-gray-500">Aucun ayant droit concerné.</p>
            @endif
        </x-filament::section>

        <x-filament::section heading="Diplômes & Formations">
            @if($newDiplomas->isNotEmpty())
                <h4 class="text-sm font-semibold text-success-600 mb-2">➕ Nouveaux à créer ({{ $newDiplomas->count() }})</h4>
                <div class="space-y-2 mb-4">
                    @foreach($newDiplomas as $item)
                        <div class="p-3 rounded-lg bg-success-50 dark:bg-success-900/20 text-sm">
                            <strong>{{ $item['title'] }}</strong> — {{ $item['institution'] }} ({{ $item['year_obtained'] }})
                        </div>
                    @endforeach
                </div>
            @endif

            @if($updatedDiplomas->isNotEmpty())
                <h4 class="text-sm font-semibold text-primary-600 mb-2">✏️ Mises à jour ({{ $updatedDiplomas->count() }})</h4>
                <div class="space-y-2 mb-4">
                    @foreach($updatedDiplomas as $pair)
                        <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20 text-sm">
                            <strong>{{ $pair['old']->title }}</strong> → {{ $pair['new']['title'] }} ({{ $pair['new']['institution'] }}, {{ $pair['new']['year_obtained'] }})
                        </div>
                    @endforeach
                </div>
            @endif

            @if($unaddressedDiplomas->isNotEmpty())
                <h4 class="text-sm font-semibold text-warning-600 mb-2">⚠️ Existants non repris dans la soumission ({{ $unaddressedDiplomas->count() }})</h4>
                <div class="space-y-2">
                    @foreach($unaddressedDiplomas as $diploma)
                        <div class="p-3 rounded-lg bg-warning-50 dark:bg-warning-900/20 text-sm flex items-center justify-between">
                            <span><strong>{{ $diploma->title }}</strong> — {{ $diploma->institution }}</span>
                            @if($this->record->isSubmitted())
                                <button wire:click="deactivateDiploma({{ $diploma->id }})"
                                        wire:confirm="Retirer ce diplôme ?"
                                        class="text-xs px-2 py-1 rounded bg-warning-200 dark:bg-warning-800 hover:opacity-80">
                                    Retirer
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if($newDiplomas->isEmpty() && $updatedDiplomas->isEmpty() && $unaddressedDiplomas->isEmpty())
                <p class="text-sm text-gray-500">Aucun diplôme/formation concerné.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>