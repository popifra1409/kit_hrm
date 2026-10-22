<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\CensusSubmission;
use App\Models\Employee;
use App\Support\CensusGap;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Point général du recensement : l'état de toutes les fiches agents, triées sur ce
 * qui reste à renseigner. L'écran répond à « qui n'a pas encore tout donné ? » sans
 * ouvrir dossier par dossier, et se filtre sur un champ précis pour préparer une
 * relance ciblée.
 */
class CensusSynthesis extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Point Recensement';

    protected static ?string $title = 'Point Général du Recensement';

    protected static ?string $navigationGroup = '👥 Gestion du Personnel';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.census-synthesis';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_census_submissions') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Agent')
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('currentService.name')
                    ->label('Service')
                    ->placeholder('Affectation à préciser')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('missing_count')
                    ->label('Champs manquants')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state) => match (true) {
                        (int) $state === 0 => 'success',
                        (int) $state <= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn(string $state) => (int) $state === 0 ? 'Complet' : (string) (int) $state),

                // La liste elle-même, pour éviter d'ouvrir la fiche pour trois cases vides.
                Tables\Columns\TextColumn::make('missing_fields')
                    ->label('Détail des champs')
                    ->getStateUsing(fn(Employee $record) => implode(', ', array_map(
                        [CensusSubmission::class, 'fieldLabel'],
                        CensusGap::missingKeys($record)
                    )))
                    ->placeholder('—')
                    ->wrap()
                    ->limit(70)
                    ->tooltip(fn(Employee $record) => implode(', ', array_map(
                        [CensusSubmission::class, 'fieldLabel'],
                        CensusGap::missingKeys($record)
                    ))),

                Tables\Columns\TextColumn::make('census_status')
                    ->label('Recensement')
                    ->badge()
                    ->getStateUsing(fn(Employee $record) => $this->latestSubmission($record)?->status)
                    ->formatStateUsing(fn(?string $state) => $state === null || $state === ''
                        ? 'Jamais soumis'
                        : $this->shortStatusLabel($state))
                    ->description(fn(Employee $record) => $this->latestSubmission($record)?->submitted_at?->format('d/m/Y'))
                    ->color(fn(Employee $record) => $this->statusColor($record)),
            ])
            ->filters([
                // Le filtre qui donne son nom à l'écran : un champ du catalogue RH.
                Tables\Filters\SelectFilter::make('gap')
                    ->label('Champ manquant')
                    ->options(CensusGap::filterOptions())
                    ->query(fn(Builder $query, array $data) => CensusGap::applyFilter($query, $data['value'] ?? null)),

                Tables\Filters\SelectFilter::make('census_status')
                    ->label('Recensement')
                    ->options([
                        'none' => 'Jamais soumis',
                        'pending' => 'En cours de validation',
                        'validated' => 'Validé',
                        'rejected' => 'Rejeté',
                        'canceled' => 'Annulé par les RH',
                    ])
                    ->query(fn(Builder $query, array $data) => $this->applyStatusFilter($query, $data['value'] ?? null)),

                Tables\Filters\SelectFilter::make('current_service_id')
                    ->label('Service')
                    ->relationship('currentService', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('open_file')
                    ->label('Fiche')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn(Employee $record) => EmployeeResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('missing_count', 'desc')
            ->paginated([25, 50, 100]);
    }

    /**
     * Une ligne = un agent actif, enrichi des booléens « champ manquant » et du total
     * calculés dans la requête : badge, tri et filtre restent d'accord entre eux.
     */
    protected function getQuery(): Builder
    {
        return Employee::query()
            ->select('employees.*')
            ->addSelect(CensusGap::selection())
            ->with(['currentService', 'censusSubmissions'])
            ->where('employees.is_active', true);
    }

    protected function latestSubmission(Employee $record): ?CensusSubmission
    {
        return $record->censusSubmissions->sortByDesc('submitted_at')->first();
    }

    protected function shortStatusLabel(string $status): string
    {
        return match ($status) {
            CensusSubmission::STATUS_SUBMITTED => 'À valider (Carrière)',
            CensusSubmission::STATUS_CAREER_VALIDATED => 'À valider (Solde)',
            CensusSubmission::STATUS_SOLDE_VALIDATED => 'À valider (finale)',
            CensusSubmission::STATUS_CAREER_REJECTED => 'Rejeté — Carrière',
            CensusSubmission::STATUS_SOLDE_REJECTED => 'Rejeté — Solde',
            CensusSubmission::STATUS_VALIDATED => 'Validé',
            CensusSubmission::STATUS_REJECTED => 'Rejeté',
            CensusSubmission::STATUS_CANCELED => 'Annulé',
            default => $status,
        };
    }

    protected function statusColor(Employee $record): string
    {
        $status = $this->latestSubmission($record)?->status;

        return match ($status) {
            CensusSubmission::STATUS_VALIDATED => 'success',
            CensusSubmission::STATUS_REJECTED,
            CensusSubmission::STATUS_CAREER_REJECTED,
            CensusSubmission::STATUS_SOLDE_REJECTED => 'danger',
            CensusSubmission::STATUS_SUBMITTED,
            CensusSubmission::STATUS_CAREER_VALIDATED,
            CensusSubmission::STATUS_SOLDE_VALIDATED => 'warning',
            CensusSubmission::STATUS_CANCELED => 'gray',
            default => 'gray',
        };
    }

    protected function applyStatusFilter(Builder $query, ?string $value): void
    {
        $pending = [
            CensusSubmission::STATUS_SUBMITTED,
            CensusSubmission::STATUS_CAREER_VALIDATED,
            CensusSubmission::STATUS_SOLDE_VALIDATED,
        ];

        $rejected = [
            CensusSubmission::STATUS_REJECTED,
            CensusSubmission::STATUS_CAREER_REJECTED,
            CensusSubmission::STATUS_SOLDE_REJECTED,
        ];

        match ($value) {
            'none' => $query->whereDoesntHave('censusSubmissions'),
            'pending' => $query->whereHas('censusSubmissions', fn(Builder $q) => $q->whereIn('status', $pending)),
            'validated' => $query->whereHas('censusSubmissions', fn(Builder $q) => $q->where('status', CensusSubmission::STATUS_VALIDATED)),
            'rejected' => $query->whereHas('censusSubmissions', fn(Builder $q) => $q->whereIn('status', $rejected)),
            'canceled' => $query->whereHas('censusSubmissions', fn(Builder $q) => $q->where('status', CensusSubmission::STATUS_CANCELED)),
            default => $query,
        };
    }

    /** Cartes d'en-tête : la photo du terrain avant tout filtrage. */
    public function getStats(): array
    {
        $base = Employee::query()->where('employees.is_active', true);

        $complete = (clone $base)->whereRaw(
            '(' . implode(' or ', array_map([CensusGap::class, 'missingSql'], CensusGap::keys())) . ') = false'
        )->count();

        return [
            'total' => (clone $base)->count(),
            'incomplete' => (clone $base)->count() - $complete,
            'never_submitted' => (clone $base)->whereDoesntHave('censusSubmissions')->count(),
            'validated' => (clone $base)
                ->whereHas('censusSubmissions', fn(Builder $q) => $q->where('status', CensusSubmission::STATUS_VALIDATED))
                ->count(),
        ];
    }
}
