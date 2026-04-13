<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AssignmentHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'assignmentHistory';

    protected static ?string $title = 'Historique des Affectations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('assignment_type')
            ->columns([
                Tables\Columns\BadgeColumn::make('assignment_type')
                    ->label('Type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'position' => 'Poste',
                        'department' => 'Département',
                        'service' => 'Service',
                        'location' => 'Lieu',
                        'contract_type' => 'Contrat',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('old_department.name')
                    ->label('Ancien')
                    ->default('—'),

                Tables\Columns\TextColumn::make('new_department.name')
                    ->label('Nouveau')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date')
                    ->date('d/m/Y'),

                Tables\Columns\IconColumn::make('is_temporary')
                    ->label('Temp.')
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
                Forms\Components\Select::make('assignment_type')
                    ->label('Type')
                    ->options([
                        'position' => 'Poste',
                        'department' => 'Département',
                        'service' => 'Service',
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
