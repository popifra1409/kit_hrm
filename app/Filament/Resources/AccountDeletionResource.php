<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountDeletionResource\Pages;
use App\Models\AccountDeletion;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountDeletionResource extends Resource
{
    protected static ?string $model = AccountDeletion::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';
    protected static ?string $navigationGroup = '👥 Gestion du Personnel';
    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return 'Suppression de Compte';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Historique des Suppressions de Comptes';
    }

    public static function canCreate(): bool
    {
        return false; // Journal en lecture seule — les suppressions passent par les actions dédiées
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('delete_user_accounts') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->description(fn(AccountDeletion $record) => $record->user_email),

                Tables\Columns\BadgeColumn::make('reason')
                    ->label('Motif')
                    ->formatStateUsing(fn(AccountDeletion $record) => $record->reason_label)
                    ->colors([
                        'warning' => 'resignation',
                        'danger' => 'death',
                        'success' => 'retirement',
                        'gray' => 'other',
                    ]),

                Tables\Columns\TextColumn::make('initiated_by')
                    ->label('Initiée par')
                    ->formatStateUsing(fn($state) => $state === 'self' ? "L'employé lui-même" : 'Un administrateur')
                    ->badge()
                    ->color(fn($state) => $state === 'self' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('deletedBy.name')
                    ->label('Supprimé par')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->label('Motif')
                    ->options(AccountDeletion::REASONS),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountDeletions::route('/'),
        ];
    }
}
