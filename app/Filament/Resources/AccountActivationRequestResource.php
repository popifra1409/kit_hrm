<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountActivationRequestResource\Pages;
use App\Models\AccountActivationRequest;
use App\Services\AccountActivationReviewService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * File d'attente des employés qui n'ont pas pu prouver leur date de recrutement
 * et qui se présentent au guichet avec une référence. En lecture seule : une
 * demande naît uniquement de l'écran d'activation.
 */
class AccountActivationRequestResource extends Resource
{
    protected static ?string $model = AccountActivationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Demandes d’activation RH';

    protected static ?string $navigationGroup = '🗂️ Recensement & Activation';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Demande d\'Activation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Demandes d\'Activation RH';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_account_activation_requests') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = AccountActivationRequest::where('status', AccountActivationRequest::STATUS_PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->description(fn(AccountActivationRequest $record) => $record->matricule),

                // Comparaison immédiate au guichet : ce que l'employé annonce vs la fiche.
                Tables\Columns\TextColumn::make('self_declared_date')
                    ->label('Déclaré par l\'employé')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('employee.recruitment_date')
                    ->label('Date du dossier')
                    ->date('d/m/Y')
                    ->placeholder('Non enregistrée'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn(AccountActivationRequest $record) => $record->status_label)
                    ->colors([
                        'warning' => fn(AccountActivationRequest $record) => $record->isValidatable(),
                        'gray' => fn(AccountActivationRequest $record) => $record->isPending() && $record->isExpired(),
                        'success' => fn(AccountActivationRequest $record) => $record->status === AccountActivationRequest::STATUS_VALIDATED,
                        'danger' => fn(AccountActivationRequest $record) => $record->status === AccountActivationRequest::STATUS_REJECTED,
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reçue le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('Traité par')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Motif du rejet')
                    ->limit(50)
                    ->placeholder('—')
                    ->tooltip(fn(AccountActivationRequest $record) => $record->rejection_reason),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'validated' => 'Validée',
                        'rejected' => 'Rejetée',
                    ]),
            ])
            ->actions([
                // Ouverture du compte : la seule action qui applique le mot de passe
                // choisi par l'employé. L'agent doit avoir vérifié l'identité en personne.
                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('validate')
                    ->visible(fn(AccountActivationRequest $record) => $record->isValidatable()
                        && auth()->user()->can('validate_account_activations'))
                    ->modalHeading('Valider cette demande d\'activation')
                    ->modalDescription('Le compte sera ouvert avec le mot de passe que l\'employé a choisi lors de sa démarche, et il pourra se connecter immédiatement.')
                    ->modalSubmitActionLabel('Ouvrir le compte')
                    ->form([
                        Forms\Components\Checkbox::make('identity_checked')
                            ->label('J\'ai vérifié l\'identité de cette personne au guichet (pièce d\'identité ou attestation RH).')
                            ->required(),
                    ])
                    ->action(function (AccountActivationRequest $record, array $data) {
                        app(AccountActivationReviewService::class)->validate(auth()->user(), $record);

                        Notification::make()
                            ->title('Compte activé')
                            ->body("{$record->employee?->full_name} peut maintenant se connecter.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('reject')
                    ->visible(fn(AccountActivationRequest $record) => $record->isPending()
                        && auth()->user()->can('validate_account_activations'))
                    ->modalHeading('Rejeter cette demande')
                    ->modalDescription('Le compte reste fermé et le mot de passe temporaire conserve son effet. La personne peut relancer une demande après coup.')
                    ->modalSubmitActionLabel('Rejeter la demande')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif (identités non correspondantes, personne non connue…)')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (AccountActivationRequest $record, array $data) {
                        app(AccountActivationReviewService::class)->reject(auth()->user(), $record, $data['reason']);

                        Notification::make()
                            ->title('Demande rejetée')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountActivationRequests::route('/'),
        ];
    }
}
