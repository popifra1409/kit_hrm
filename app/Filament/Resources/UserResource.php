<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\AccountDeletion;
use App\Services\AccountDeletionService;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Employé lié (compte mobile)')
                    ->options(fn() => \App\Models\Employee::where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn($e) => [$e->id => $e->full_name . ' (' . $e->matricule . ')']))
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return \App\Models\Employee::where('is_active', true)
                            ->where(function ($q) use ($search) {
                                $q->where('matricule', 'ilike', "%{$search}%")
                                    ->orWhere('first_name', 'ilike', "%{$search}%")
                                    ->orWhere('last_name', 'ilike', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($e) => [$e->id => $e->full_name . ' (' . $e->matricule . ')']);
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $record = \App\Models\Employee::find($value);
                        return $record ? $record->full_name . ' (' . $record->matricule . ')' : null;
                    })
                    ->nullable()
                    ->helperText('Laissez vide pour un compte purement administratif (sans accès mobile employé).'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->nullable()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn($context) => $context === 'create')
                    ->dehydrated(fn($state) => filled($state))
                    ->maxLength(255)
                    ->helperText(fn($context) => $context === 'create'
                        ? "Mot de passe temporaire à communiquer à l'employé pour l'activation de son compte mobile."
                        : null),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText("Si l'employé active son compte mobile sans rôle assigné ici, le rôle 'employee' lui sera attribué automatiquement."),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.matricule')
                    ->label('Employé lié')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->searchable(),
                Tables\Columns\IconColumn::make('activated_at')
                    ->label('Activé')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->activated_at !== null),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete_account')
                    ->label('Supprimer le compte')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->visible(fn(User $record) => $record->employee_id !== null && auth()->user()->can('delete_user_accounts'))
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer ce compte utilisateur')
                    ->modalDescription('Seul le compte de connexion sera supprimé — la fiche employé et toutes ses informations restent intactes en base.')
                    ->form([
                        Forms\Components\Select::make('reason')
                            ->label('Motif')
                            ->options(AccountDeletion::REASONS)
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Précisions (optionnel)')
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data) {
                        try {
                            app(AccountDeletionService::class)->delete(
                                user: $record,
                                reason: $data['reason'],
                                notes: $data['notes'] ?? null,
                                initiatedBy: 'admin',
                                deletedBy: auth()->user(),
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Compte supprimé')
                                ->body("Le compte de {$record->name} a été supprimé. La fiche employé reste intacte.")
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Suppression impossible')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '🔧 Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
