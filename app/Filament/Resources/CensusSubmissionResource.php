<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CensusSubmissionResource\Pages;
use App\Models\CensusSubmission;
use App\Models\CensusCampaign;
use App\Services\CensusValidationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CensusSubmissionResource extends Resource
{
    protected static ?string $model = CensusSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return 'Soumission de Recensement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Soumissions de Recensement';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable()
                    ->description(fn(CensusSubmission $record) => $record->employee?->matricule),

                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campagne')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    // Libellé complet du circuit (les 8 statuts) : trois seulement
                    // étaient traduits, « career_validated » s'affichait en brut.
                    ->formatStateUsing(fn(CensusSubmission $record) => $record->stage_label)
                    ->colors([
                        'warning' => fn(CensusSubmission $record) => $record->isCancelable(),
                        'success' => fn(CensusSubmission $record) => $record->isValidated(),
                        'danger' => fn(CensusSubmission $record) => $record->isRejected(),
                        'gray' => fn(CensusSubmission $record) => $record->status === CensusSubmission::STATUS_CANCELED,
                    ]),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('Traité par')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('census_campaign_id')
                    ->label('Campagne')
                    ->options(fn() => CensusCampaign::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'submitted' => 'En attente — Carrière',
                        'career_validated' => 'En attente — Solde',
                        'solde_validated' => 'En attente — validation finale',
                        'career_rejected' => 'Rejeté — Carrière',
                        'solde_rejected' => 'Rejeté — Solde',
                        'validated' => 'Validé',
                        'rejected' => 'Rejeté définitivement',
                        'canceled' => 'Annulé par les RH',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Examiner')
                    ->icon('heroicon-o-magnifying-glass')
                    ->url(fn(CensusSubmission $record) => static::getUrl('review', ['record' => $record])),

                // Annulation RH : seul moyen de rendre la main à l'employé sans passer
                // par un rejet (qui, lui, reste un constat négatif avec motif d'étape).
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('cancel')
                    ->visible(fn(CensusSubmission $record) => $record->isCancelable() && auth()->user()->can('cancel_census_submissions'))
                    ->modalHeading('Annuler ce recensement')
                    ->modalDescription('Le circuit de validation est remis à zéro et l\'employé reprend son recensement depuis le début. Rien n\'a été appliqué à sa fiche : les données ne sont écrites qu\'à la validation finale.')
                    ->modalSubmitActionLabel('Annuler le recensement')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif (affiché à l\'employé)')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (CensusSubmission $record, array $data) {
                        app(CensusValidationService::class)->cancel($record, auth()->id(), $data['reason']);

                        Notification::make()
                            ->title('Recensement annulé')
                            ->body("L'employé peut reprendre son recensement.")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCensusSubmissions::route('/'),
            'review' => Pages\ReviewCensusSubmission::route('/{record}/review'),
        ];
    }
}
