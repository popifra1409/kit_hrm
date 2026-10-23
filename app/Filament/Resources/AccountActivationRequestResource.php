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
use Illuminate\Validation\ValidationException;

/**
 * File d'attente des employés qui n'ont pas pu prouver leur date de recrutement
 * et qui se présentent au Service RH avec une référence. En lecture seule : une
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

                // Deux démarches dans la même file : le traitement n'est pas le même.
                Tables\Columns\TextColumn::make('type')
                    ->label('Démarche')
                    ->badge()
                    ->formatStateUsing(fn(AccountActivationRequest $record) => $record->type_label)
                    ->color(fn(AccountActivationRequest $record) => $record->isPasswordReset() ? 'info' : 'primary'),

                // Comparaison immédiate au Service RH : ce que l'employé annonce vs la fiche.
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Démarche')
                    ->options([
                        'activation' => 'Activation du compte',
                        'password_reset' => 'Réinitialisation du mot de passe',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'validated' => 'Validée',
                        'rejected' => 'Rejetée',
                    ]),
            ])
            ->actions([
                // La seule action qui applique un mot de passe. L'agent doit avoir
                // vérifié l'identité de la personne en personne.
                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('validate')
                    ->visible(fn(AccountActivationRequest $record) => $record->isValidatable()
                        && auth()->user()->can('validate_account_activations'))
                    ->modalHeading(fn(AccountActivationRequest $record) => $record->isPasswordReset()
                        ? 'Régénérer le mot de passe de cette personne'
                        : 'Valider cette demande d\'activation')
                    ->modalDescription(fn(AccountActivationRequest $record) => $record->isPasswordReset()
                        ? 'Un mot de passe temporaire sera généré et affiché une seule fois : remettez-le ou lisez-le à la personne immédiatement, il n\'est conservé nulle part.'
                        : 'Le compte sera ouvert avec le mot de passe que l\'employé a choisi lors de sa démarche, et il pourra se connecter immédiatement.')
                    ->modalSubmitActionLabel(fn(AccountActivationRequest $record) => $record->isPasswordReset()
                        ? 'Générer le mot de passe'
                        : 'Ouvrir le compte')
                    ->form([
                        Forms\Components\Checkbox::make('identity_checked')
                            ->label('J\'ai vérifié l\'identité de cette personne au Service RH (pièce d\'identité ou attestation).')
                            ->required(),
                    ])
                    ->action(function (AccountActivationRequest $record) {
                        try {
                            $temporaryPassword = app(AccountActivationReviewService::class)
                                ->validate(auth()->user(), $record);
                        } catch (ValidationException $e) {
                            // Le service refuse : sans ce message visible, la fenêtre se
                            // contente de se refermer et le clic paraît sans effet.
                            Notification::make()
                                ->title('Demande non traitée')
                                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($temporaryPassword) {
                            // persistent() : rien n'expire pendant que l'agent le dicte.
                            Notification::make()
                                ->title('Nouveau mot de passe temporaire')
                                ->body("À remettre sur-le-champ à {$record->employee?->full_name} : {$temporaryPassword}")
                                ->success()
                                ->persistent()
                                ->send();

                            return;
                        }

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
                        try {
                            app(AccountActivationReviewService::class)->reject(auth()->user(), $record, $data['reason']);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Rejet non enregistré')
                                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Demande rejetée')
                            ->success()
                            ->send();
                    }),

                // Le mot de passe généré n'est stocké nulle part : si la personne a
                // raccroché avant de le noter, l'agent doit pouvoir en produire un autre.
                Tables\Actions\Action::make('regenerate')
                    ->label('Nouveau mot de passe')
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->authorize('regenerate')
                    ->visible(fn(AccountActivationRequest $record) => $record->isPasswordReset()
                        && $record->status === AccountActivationRequest::STATUS_VALIDATED
                        && auth()->user()->can('validate_account_activations'))
                    ->requiresConfirmation()
                    ->modalHeading('Générer un nouveau mot de passe temporaire')
                    ->modalDescription('Le mot de passe précédent cesse de fonctionner dès ici : à dicter immédiatement à la personne.')
                    ->modalSubmitActionLabel('Générer')
                    ->action(function (AccountActivationRequest $record) {
                        $temporaryPassword = app(AccountActivationReviewService::class)
                            ->regenerateTemporaryPassword($record);

                        Notification::make()
                            ->title('Nouveau mot de passe temporaire')
                            ->body("À remettre sur-le-champ à {$record->employee?->full_name} : {$temporaryPassword}")
                            ->success()
                            ->persistent()
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
