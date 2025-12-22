<?php

namespace App\Filament\Resources\TrainingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $title = 'Participants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Employé')
                    ->options(function () {
                        return \App\Models\Employee::query()
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn($employee) => [
                                $employee->id => $employee->full_name . ' (' . $employee->matricule . ')'
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->preload()
                    ->native(false),

                Forms\Components\Select::make('registration_status')
                    ->label('Statut Inscription')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'rejected' => 'Rejetée',
                        'waitlist' => 'Liste d\'attente',
                    ])
                    ->default('pending')
                    ->required()
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee.full_name')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.matricule')
                    ->label('Matricule')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('registration_status')
                    ->label('Inscription')
                    ->formatStateUsing(fn($record) => $record->getRegistrationStatusLabel())
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'waitlist',
                    ]),

                Tables\Columns\BadgeColumn::make('attendance_status')
                    ->label('Présence')
                    ->formatStateUsing(fn($record) => $record->getAttendanceStatusLabel())
                    ->colors([
                        'gray' => 'registered',
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'partial',
                    ]),

                Tables\Columns\IconColumn::make('certificate_issued')
                    ->label('Certificat')
                    ->boolean()
                    ->trueColor('success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ajouter Participant'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->registration_status === 'pending')
                    ->action(fn($record) => $record->approve()),

                Tables\Actions\Action::make('mark_present')
                    ->label('Présent')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->registration_status === 'approved')
                    ->action(fn($record) => $record->markPresent()),

                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Retirer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Retirer'),
                ]),
            ]);
    }
}
