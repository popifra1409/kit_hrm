<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AdvancementHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'advancementHistory';

    protected static ?string $title = 'Historique des Avancements';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('advancement_type')
            ->columns([
                Tables\Columns\BadgeColumn::make('advancement_type')
                    ->label('Type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'echelon' => 'Échelon',
                        'category' => 'Catégorie',
                        'grade' => 'Grade',
                        'salary_adjustment' => 'Salaire',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('old_echelon')
                    ->label('Ancien')
                    ->formatStateUsing(function ($record) {
                        if ($record->advancement_type === 'echelon') {
                            return "Éch. {$record->old_echelon}";
                        }
                        return "Cat. {$record->old_category}";
                    }),

                Tables\Columns\TextColumn::make('new_echelon')
                    ->label('Nouveau')
                    ->weight('bold')
                    ->formatStateUsing(function ($record) {
                        if ($record->advancement_type === 'echelon') {
                            return "Éch. {$record->new_echelon}";
                        }
                        return "Cat. {$record->new_category}";
                    }),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date')
                    ->date('d/m/Y'),

                Tables\Columns\IconColumn::make('is_automatic')
                    ->label('Auto')
                    ->boolean(),

                Tables\Columns\TextColumn::make('decision_number')
                    ->label('N° Décision'),
            ])
            ->defaultSort('effective_date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('advancement_type')
                    ->label('Type')
                    ->options([
                        'echelon' => 'Échelon',
                        'category' => 'Catégorie',
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('effective_date')
                    ->label('Date d\'Effet')
                    ->required()
                    ->default(now()),

                Forms\Components\Textarea::make('reason')
                    ->label('Motif')
                    ->rows(2),
            ]);
    }
}
