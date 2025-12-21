<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function getModelLabel(): string
    {
        return 'Pointage';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pointages';
    }

    public static function getNavigationGroup(): ?string
    {
        return '📅 Congés & Absences';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employé et Date')
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

                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'present' => '✅ Présent',
                                'absent' => '❌ Absent',
                                'late' => '⏰ En retard',
                                'half_day' => '🕐 Demi-journée',
                                'on_leave' => '📅 En congé',
                                'on_mission' => '✈️ En mission',
                                'sick' => '🏥 Malade',
                            ])
                            ->default('present')
                            ->required()
                            ->reactive()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Horaires de Travail')
                    ->schema([
                        Forms\Components\TimePicker::make('clock_in')
                            ->label('Heure d\'arrivée')
                            ->seconds(false)
                            ->default('07:30')
                            ->required(fn($get) => $get('status') === 'present'),

                        Forms\Components\TimePicker::make('clock_out')
                            ->label('Heure de départ')
                            ->seconds(false)
                            ->default('15:30')
                            ->after('clock_in'),
                    ])
                    ->columns(2)
                    ->visible(fn($get) => in_array($get('status'), ['present', 'late', 'half_day'])),

                Forms\Components\Section::make('Pause')
                    ->schema([
                        Forms\Components\TimePicker::make('break_start')
                            ->label('Début pause')
                            ->seconds(false)
                            ->default('12:00'),

                        Forms\Components\TimePicker::make('break_end')
                            ->label('Fin pause')
                            ->seconds(false)
                            ->default('13:00')
                            ->after('break_start'),

                        Forms\Components\TextInput::make('break_duration')
                            ->label('Durée pause (heures)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(1),
                    ])
                    ->columns(3)
                    ->visible(fn($get) => $get('status') === 'present')
                    ->collapsible(),

                Forms\Components\Section::make('Calculs Automatiques')
                    ->schema([
                        Forms\Components\TextInput::make('total_hours')
                            ->label('Heures totales')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('h'),

                        Forms\Components\TextInput::make('regular_hours')
                            ->label('Heures normales')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('h'),

                        Forms\Components\TextInput::make('overtime_hours')
                            ->label('Heures supplémentaires')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('h'),

                        Forms\Components\TextInput::make('late_minutes')
                            ->label('Retard (minutes)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('min'),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Forms\Components\Section::make('Notes et Justification')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('justification_document')
                            ->label('Document Justificatif')
                            ->directory('attendances/justifications')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->visible(fn($get) => in_array($get('status'), ['absent', 'sick', 'late']))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.matricule')
                    ->label('Matricule')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('clock_in')
                    ->label('Arrivée')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('clock_out')
                    ->label('Départ')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->colors([
                        'success' => 'present',
                        'danger' => fn($state) => in_array($state, ['absent', 'sick']),
                        'warning' => 'late',
                        'info' => 'half_day',
                        'primary' => 'on_leave',
                        'secondary' => 'on_mission',
                    ]),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Heures')
                    ->formatStateUsing(fn($state) => $state . 'h')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('overtime_hours')
                    ->label('H. Supp.')
                    ->formatStateUsing(fn($state) => $state > 0 ? $state . 'h' : '—')
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('Retard')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_validated')
                    ->label('Validé')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employé')
                    ->options(function () {
                        return \App\Models\Employee::query()
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn($employee) => [
                                $employee->id => $employee->full_name
                            ]);
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'late' => 'En retard',
                        'half_day' => 'Demi-journée',
                        'on_leave' => 'En congé',
                        'on_mission' => 'En mission',
                        'sick' => 'Malade',
                    ]),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('date', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('En retard'),

                Tables\Filters\TernaryFilter::make('is_validated')
                    ->label('Validé'),
            ])
            ->actions([
                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_validated)
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->validate())
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Pointage validé')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('validate')
                        ->label('Valider sélection')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->validate();
                            }
                        }),

                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
