<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsenceResource\Pages;
use App\Models\Absence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AbsenceResource extends Resource
{
    protected static ?string $model = Absence::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function getModelLabel(): string
    {
        return 'Absence/Permission';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Absences & Permissions';
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
                Forms\Components\Section::make('Informations de l\'Absence')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->native(false)
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Employee::query()
                                    ->where('is_active', true)
                                    ->where(function ($query) use ($search) {
                                        $query->whereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"])
                                            ->orWhere('matricule', 'ILIKE', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn($employee) => [
                                        $employee->id => $employee->full_name . ' (' . $employee->matricule . ')'
                                    ]);
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $employee = \App\Models\Employee::find($value);
                                return $employee ? $employee->full_name . ' (' . $employee->matricule . ')' : '';
                            }),

                        Forms\Components\Select::make('type')
                            ->label('Type d\'Absence')
                            ->options([
                                'exceptional' => '🎗️ Permission Exceptionnelle',
                                'personal' => '👤 Convenance Personnelle',
                                'medical' => '🏥 Repos Médical',
                                'late_arrival' => '⏰ Retard',
                                'early_departure' => '🏃 Départ Anticipé',
                                'administrative' => '📋 Autorisation Administrative',
                            ])
                            ->required()
                            ->reactive()
                            ->native(false),

                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Horaires')
                    ->schema([
                        Forms\Components\Toggle::make('is_full_day')
                            ->label('Absence toute la journée')
                            ->reactive()
                            ->default(false),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Heure de début')
                            ->seconds(false)
                            ->visible(fn($get) => !$get('is_full_day'))
                            ->required(fn($get) => !$get('is_full_day')),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Heure de fin')
                            ->seconds(false)
                            ->visible(fn($get) => !$get('is_full_day'))
                            ->required(fn($get) => !$get('is_full_day'))
                            ->after('start_time'),

                        Forms\Components\TextInput::make('hours')
                            ->label('Durée (heures)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Calculé automatiquement'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Motif et Justification')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif')
                            ->required()
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('justification_document')
                            ->label('Document Justificatif')
                            ->directory('absences/justifications')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->helperText('Certificat médical, justificatif, etc.')
                            ->visible(fn($get) => in_array($get('type'), ['medical', 'exceptional']))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Déduction sur Paie')
                    ->schema([
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Absence payée')
                            ->default(true)
                            ->reactive(),

                        Forms\Components\TextInput::make('deduction_amount')
                            ->label('Montant à déduire (FCFA)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0)
                            ->visible(fn($get) => !$get('is_paid')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validation')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'rejected' => 'Rejetée',
                            ])
                            ->default('pending')
                            ->required()
                            ->reactive()
                            ->native(false),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->rows(2)
                            ->visible(fn($get) => $get('status') === 'rejected')
                            ->required(fn($get) => $get('status') === 'rejected'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn($context) => $context !== 'create'),
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

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'info' => 'exceptional',
                        'primary' => 'personal',
                        'danger' => 'medical',
                        'warning' => fn($state) => in_array($state, ['late_arrival', 'early_departure']),
                        'secondary' => 'administrative',
                    ])
                    ->formatStateUsing(fn($record) => $record->getTypeLabel()),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Durée')
                    ->formatStateUsing(fn($record) => $record->is_full_day
                        ? '8h (Journée)'
                        : $record->hours . 'h')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn($record) => $record->getStatusLabel()),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Payée')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'full_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'exceptional' => 'Permission Exceptionnelle',
                        'personal' => 'Convenance Personnelle',
                        'medical' => 'Repos Médical',
                        'late_arrival' => 'Retard',
                        'early_departure' => 'Départ Anticipé',
                        'administrative' => 'Autorisation Administrative',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'rejected' => 'Rejetée',
                    ]),

                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payée')
                    ->placeholder('Toutes')
                    ->trueLabel('Payées')
                    ->falseLabel('Non payées'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->approve())
                    ->after(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Absence approuvée')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject($data['rejection_reason']);

                        \Filament\Notifications\Notification::make()
                            ->title('Absence rejetée')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsences::route('/'),
            'create' => Pages\CreateAbsence::route('/create'),
            'edit' => Pages\EditAbsence::route('/{record}/edit'),
        ];
    }
}
