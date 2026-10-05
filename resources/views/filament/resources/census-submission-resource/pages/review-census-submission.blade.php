<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            @php
                $stageColors = [
                    'submitted' => 'warning',
                    'career_validated' => 'warning',
                    'solde_validated' => 'warning',
                    'validated' => 'success',
                    'career_rejected' => 'danger',
                    'solde_rejected' => 'danger',
                    'rejected' => 'danger',
                ];
                $color = $stageColors[$this->record->status] ?? 'gray';
            @endphp
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Étape actuelle</p>
                    <p class="text-lg font-bold text-{{ $color }}-600">{{ $this->record->stage_label }}</p>
                </div>
                <div class="flex gap-2 text-xs">
                    <span class="px-2 py-1 rounded {{ in_array($this->record->status, ['career_validated','solde_validated','validated']) ? 'bg-success-100 text-success-700' : ($this->record->status === 'career_rejected' ? 'bg-danger-100 text-danger-700' : 'bg-gray-100 text-gray-500') }}">1. Carrière</span>
                    <span class="px-2 py-1 rounded {{ in_array($this->record->status, ['solde_validated','validated']) ? 'bg-success-100 text-success-700' : ($this->record->status === 'solde_rejected' ? 'bg-danger-100 text-danger-700' : 'bg-gray-100 text-gray-500') }}">2. Solde</span>
                    <span class="px-2 py-1 rounded {{ $this->record->status === 'validated' ? 'bg-success-100 text-success-700' : ($this->record->status === 'rejected' ? 'bg-danger-100 text-danger-700' : 'bg-gray-100 text-gray-500') }}">3. Admin</span>
                </div>
            </div>

            @if($this->record->career_rejection_reason)
                <p class="text-sm text-danger-600 mt-2">Motif rejet Carrière : {{ $this->record->career_rejection_reason }}</p>
            @endif
            @if($this->record->solde_rejection_reason)
                <p class="text-sm text-danger-600 mt-2">Motif rejet Solde : {{ $this->record->solde_rejection_reason }}</p>
            @endif
            @if($this->record->rejection_reason)
                <p class="text-sm text-danger-600 mt-2">Motif rejet final : {{ $this->record->rejection_reason }}</p>
            @endif
        </x-filament::section>

        @if(!empty($payload['photo_path'] ?? null))
            <x-filament::section heading="📷 Photo soumise">
                <img src="{{ Illuminate\Support\Facades\Storage::url($payload['photo_path']) }}" alt="" class="w-24 h-24 rounded-full object-cover border" />
            </x-filament::section>
        @endif

        @if(!$this->record->isValidated())
            <p class="text-xs text-gray-500 -mb-2">
                ✏️ Les sections ci-dessous sont modifiables — corrigez après vérification physique des documents de l'employé. Vos corrections sont enregistrées au moment où vous cliquez sur un bouton de validation.
            </p>
        @endif

        {{ $this->form }}

        <x-filament::section heading="🧑‍💼 Carrière — Ayants Droit">
            @if($newDependents->isNotEmpty())
                <h4 class="text-sm font-semibold text-success-600 mb-2">➕ Nouveaux à créer ({{ $newDependents->count() }})</h4>
                <div class="space-y-2 mb-4">
                    @foreach($newDependents as $item)
                        <div class="p-3 rounded-lg bg-success-50 dark:bg-success-900/20 text-sm">
                            <strong>{{ $item['last_name'] }} {{ $item['first_name'] ?? '' }}</strong> — {{ $item['relationship'] }}, né(e) le {{ \Illuminate\Support\Carbon::parse($item['birth_date'])->format('d-m-Y') }}
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
                            @if(!$this->record->isValidated())
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

        <x-filament::section heading="🧑‍💼 Carrière — Diplômes & Formations">
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
                            @if(!$this->record->isValidated())
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