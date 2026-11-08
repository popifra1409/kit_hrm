<?php

namespace App\Filament\Resources\Concerns;

use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * L'écran du journal, partagé entre la ligne chaude et l'archive : mêmes colonnes,
 * mêmes filtres, même fenêtre de détail — seule la table change.
 *
 * Rien ici n'écrit : le journal se lit. La seule action possible sur une ligne est sa
 * consultation, pour qu'aucune manipulation ne puisse réécrire l'historique.
 */
trait HasActivityLogTable
{
    protected static function journalTable(Table $table, bool $avecArchive = false): Table
    {
        $colonnes = [
            Tables\Columns\TextColumn::make('occurred_at')
                ->label('Horodatage')
                ->dateTime('d/m/Y H:i:s')
                ->sortable(),

            Tables\Columns\TextColumn::make('actor_name')
                ->label('Agent')
                ->placeholder('—')
                ->searchable()
                ->description(fn (Model $record) => $record->actor_matricule),

            Tables\Columns\TextColumn::make('action_label')
                ->label('Action')
                ->badge()
                ->color(fn (Model $record) => static::actionColor($record->action)),

            Tables\Columns\TextColumn::make('status_label')
                ->label('Résultat')
                ->badge()
                ->color(fn (Model $record) => match ($record->status) {
                    ActivityLog::STATUS_SUCCESS => 'success',
                    ActivityLog::STATUS_BLOCKED => 'warning',
                    default => 'danger',
                }),

            Tables\Columns\TextColumn::make('subject_label')
                ->label('Dossier')
                ->placeholder('—')
                ->searchable()
                ->limit(35)
                ->description(fn (Model $record) => $record->subject_type
                    ? class_basename($record->subject_type) . ($record->subject_id ? ' #' . $record->subject_id : '')
                    : null),

            Tables\Columns\TextColumn::make('reason')
                ->label('Motif')
                ->placeholder('—')
                ->searchable()
                ->limit(70)
                ->tooltip(fn (Model $record) => $record->reason),

            Tables\Columns\TextColumn::make('context_label')
                ->label('Depuis'),

            Tables\Columns\TextColumn::make('description')
                ->label('Commentaire')
                ->placeholder('—')
                ->limit(60)
                ->toggleable()
                ->tooltip(fn (Model $record) => $record->description),

            Tables\Columns\TextColumn::make('ip_address')
                ->label('Adresse IP')
                ->placeholder('—')
                ->fontFamily(FontFamily::Mono)
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        if ($avecArchive) {
            $colonnes[] = Tables\Columns\TextColumn::make('period')
                ->label('Mois archivé')
                ->badge()
                ->color('gray');

            $colonnes[] = Tables\Columns\TextColumn::make('archived_at')
                ->label('Versé le')
                ->dateTime('d/m/Y H:i')
                ->toggleable(isToggledHiddenByDefault: true);
        }

        return $table
            ->columns($colonnes)
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->label('Période')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('occurred_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('occurred_at', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Résultat')
                    ->options(ActivityLog::STATUSES),

                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options(ActivityLog::ACTIONS)
                    ->searchable(),

                Tables\Filters\SelectFilter::make('context')
                    ->label('Depuis')
                    ->options(ActivityLog::CONTEXTS),

                Tables\Filters\Filter::make('non_abouties')
                    ->label('Seulement les échecs et les blocages')
                    ->query(fn (Builder $query) => $query->whereIn('status', [
                        ActivityLog::STATUS_FAILED,
                        ActivityLog::STATUS_BLOCKED,
                    ])),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('details')
                        ->label('Voir le détail')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->infolist(fn (Model $record) => static::detailEntries($record, $avecArchive))
                        ->modalHeading('Ligne du journal')
                        ->modalDescription('Ce qui a été écrit sur le moment : une ligne du journal ne se corrige pas.')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Fermer'),
                ]),
            ], position: ActionsPosition::BeforeColumns)
            ->defaultSort('occurred_at', 'desc');
    }

    /**
     * La fenêtre de détail : toute la ligne, y compris le diff. `properties` passe par
     * describeProperties() pour se lire sans JSON, et chaque valeur est échappée.
     */
    protected static function detailEntries(Model $record, bool $avecArchive = false): array
    {
        $entrees = [
            TextEntry::make('occurred_at')
                ->label('Horodatage')
                ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i:s')),

            TextEntry::make('actor_label')->label('Agent'),
            TextEntry::make('context_label')->label('Depuis'),
            TextEntry::make('action_label')->label('Action'),
            TextEntry::make('status_label')->label('Résultat'),

            TextEntry::make('subject_label')->label('Dossier')->placeholder('—'),

            TextEntry::make('reason')->label('Motif')->placeholder('—'),
            TextEntry::make('description')->label('Commentaire')->placeholder('—'),

            TextEntry::make('properties')
                ->label('Détails consignés')
                ->formatStateUsing(fn ($state) => new HtmlString(
                    nl2br(e(ActivityLog::describeProperties(is_array($state) ? $state : null)))
                ))
                ->html(),

            TextEntry::make('ip_address')->label('Adresse IP')->placeholder('—')->fontFamily(FontFamily::Mono),
            TextEntry::make('user_agent')->label('Navigateur')->placeholder('—'),
        ];

        if ($avecArchive) {
            $entrees[] = TextEntry::make('period')->label('Mois archivé');
            $entrees[] = TextEntry::make('archived_at')
                ->label('Versé aux archives le')
                ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i'));
        }

        return $entrees;
    }

    /** Les verbes qui racontent une décision sont mis en avant, le reste reste discret. */
    protected static function actionColor(string $action): string
    {
        return match ($action) {
            'census_validated', 'activation_validated', 'login_success' => 'success',
            'census_rejected', 'census_canceled', 'activation_rejected', 'account_deletion_requested' => 'danger',
            'login_failed', 'login_locked', 'activation_failed', 'activation_locked', 'direct_entry_blocked' => 'warning',
            default => 'gray',
        };
    }
}
