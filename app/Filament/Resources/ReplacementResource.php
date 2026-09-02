<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReplacementResource\Pages;
use App\Models\Replacement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReplacementResource extends Resource
{
    protected static ?string $model = Replacement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    public static function getModelLabel(): string
    {
        return 'Remplacement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Remplacements';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 8;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employés Concernés')
                    ->schema([
                        Forms\Components\Select::make('original_employee_id')
                            ->label('Employé Absent')
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
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('replacement_employee_id')
                            ->label('Remplaçant')
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
                            ->native(false)
                            ->different('original_employee_id')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Période et Motif')
                    ->schema([
                        Forms\Components\Select::make('reason')
                            ->label('Motif du Remplacement')
                            ->options([
                                'leave' => '📅 Congé',
                                'sick_leave' => '🏥 Maladie',
                                'maternity' => '👶 Maternité',
                                'mission' => '✈️ Mission',
                                'training' => '📚 Formation',
                                'other' => '📋 Autre',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de Début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de Fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('start_date'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Affectation Temporaire')
                    ->schema([
                        Forms\Components\Select::make('temporary_service_id')
                            ->label('Service Temporaire')
                            ->relationship('temporaryService', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\Select::make('temporary_qualification_id')
                            ->label('Qualification Temporaire')
                            ->relationship('temporaryQualification', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\Textarea::make('responsibilities')
                            ->label('Responsabilités Confiées')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Rémunération Additionnelle')
                    ->schema([
                        Forms\Components\Toggle::make('has_bonus')
                            ->label('Prime de Remplacement')
                            ->reactive()
                            ->default(false),

                        Forms\Components\Select::make('bonus_type')
                            ->label('Type de Prime')
                            ->options([
                                'fixed' => 'Montant Fixe',
                                'percentage' => 'Pourcentage du Salaire',
                            ])
                            ->default('fixed')
                            ->native(false)
                            ->visible(fn($get) => $get('has_bonus')),

                        Forms\Components\TextInput::make('bonus_amount')
                            ->label('Montant/Pourcentage')
                            ->numeric()
                            ->prefix(fn($get) => $get('bonus_type') === 'fixed' ? 'FCFA' : '%')
                            ->required(fn($get) => $get('has_bonus'))
                            ->visible(fn($get) => $get('has_bonus')),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Décision Administrative')
                    ->schema([
                        Forms\Components\TextInput::make('decision_number')
                            ->label('N° de Décision')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('decision_date')
                            ->label('Date de Décision')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Évaluation du Remplacement')
                    ->schema([
                        Forms\Components\Select::make('performance_rating')
                            ->label('Note de Performance')
                            ->options([
                                1 => '⭐ Insuffisant',
                                2 => '⭐⭐ Moyen',
                                3 => '⭐⭐⭐ Bon',
                                4 => '⭐⭐⭐⭐ Très Bon',
                                5 => '⭐⭐⭐⭐⭐ Excellent',
                            ])
                            ->native(false),

                        Forms\Components\Textarea::make('performance_notes')
                            ->label('Commentaires sur la Performance')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->visible(fn($context) => $context === 'edit'),

                Forms\Components\Section::make('Statut et Notes')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                                'completed' => 'Terminé',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('originalEmployee.full_name')
                    ->label('Employé Absent')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('replacementEmployee.full_name')
                    ->label('Remplaçant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('reason')
                    ->label('Motif')
                    ->formatStateUsing(fn($record) => $record->getReasonLabel())
                    ->colors([
                        'primary' => 'leave',
                        'danger' => 'sick_leave',
                        'warning' => 'maternity',
                        'info' => 'mission',
                        'success' => 'training',
                        'secondary' => 'other',
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Durée')
                    ->formatStateUsing(fn($record) => $record->getDurationInDays() . ' jours')
                    ->sortable(false),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->color(fn($record) => $record->getStatusColor()),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('has_bonus')
                    ->label('Prime')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'completed' => 'Terminé',
                    ]),

                Tables\Filters\SelectFilter::make('reason')
                    ->label('Motif')
                    ->options([
                        'leave' => 'Congé',
                        'sick_leave' => 'Maladie',
                        'maternity' => 'Maternité',
                        'mission' => 'Mission',
                        'training' => 'Formation',
                        'other' => 'Autre',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->approve())
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Remplacement approuvé')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->reject())
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Remplacement rejeté')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\Action::make('complete')
                    ->label('Terminer')
                    ->icon('heroicon-o-check-badge')
                    ->color('gray')
                    ->visible(fn($record) => $record->status === 'approved' && $record->is_active)
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->complete())
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Remplacement terminé')
                            ->success()
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
            'index' => Pages\ListReplacements::route('/'),
            'create' => Pages\CreateReplacement::route('/create'),
            'edit' => Pages\EditReplacement::route('/{record}/edit'),
        ];
    }
}
