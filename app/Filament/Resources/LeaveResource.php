<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations sur la Demande')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(function () {
                                return \App\Models\Employee::where('is_active', true)
                                    ->get()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Calculer automatiquement le score
                                if ($state) {
                                    $leave = new \App\Models\Leave([
                                        'employee_id' => $state,
                                    ]);
                                    $leave->calculateScore();

                                    $set('anciennete_score', $leave->anciennete_score);
                                    $set('discipline_score', $leave->discipline_score);
                                    $set('children_score', $leave->children_score);
                                    $set('total_score', $leave->total_score);
                                }
                            }),

                        Forms\Components\Select::make('leave_type_id')
                            ->label('Type de congé')
                            ->relationship('leaveType', 'name')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->native(false),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $start = $get('start_date');
                                if ($start && $state) {
                                    $days = \Carbon\Carbon::parse($start)->diffInDays($state) + 1;
                                    $set('total_days', $days);
                                }
                            }),

                        Forms\Components\TextInput::make('total_days')
                            ->label('Nombre de jours')
                            ->numeric()
                            ->disabled()
                            ->suffix('jours'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Justification')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de la demande')
                            ->required()
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document_path')
                            ->label('Justificatif (si requis)')
                            ->directory('leave-documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->helperText('PDF ou image, max 5 MB'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Scores d\'Évaluation')
                    ->schema([
                        Forms\Components\TextInput::make('anciennete_score')
                            ->label('Score Ancienneté (0-10)')
                            ->numeric()
                            ->disabled()
                            ->suffix('pts')
                            ->helperText('1 point par année, max 10'),

                        Forms\Components\TextInput::make('discipline_score')
                            ->label('Score Discipline (0-10)')
                            ->numeric()
                            ->disabled()
                            ->suffix('pts')
                            ->helperText('10 - points disciplinaires'),

                        Forms\Components\TextInput::make('children_score')
                            ->label('Score Enfants < 6 ans (0-5)')
                            ->numeric()
                            ->disabled()
                            ->suffix('pts')
                            ->helperText('1 point par enfant, max 5'),

                        Forms\Components\TextInput::make('total_score')
                            ->label('Score Total')
                            ->numeric()
                            ->disabled()
                            ->suffix('pts')
                            ->extraAttributes(['class' => 'font-bold']),
                    ])
                    ->columns(4)
                    ->collapsed(),

                Forms\Components\Section::make('Statut et Notes')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved_n1' => 'Approuvé Niveau 1 (Chef Service)',
                                'approved_n2' => 'Approuvé Niveau 2 (DRH)',
                                'approved' => 'Approuvé Final',
                                'rejected' => 'Rejeté',
                                'cancelled' => 'Annulé',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('Suivi du Retour')
                    ->schema([
                        Forms\Components\Toggle::make('has_returned')
                            ->label('Employé de retour')
                            ->reactive()
                            ->disabled(fn($record) => !$record || $record->status !== 'approved'),

                        Forms\Components\DatePicker::make('actual_return_date')
                            ->label('Date de retour effective')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(fn($record) => $record?->end_date)
                            ->visible(fn($get) => $get('has_returned'))
                            ->required(fn($get) => $get('has_returned')),

                        Forms\Components\Textarea::make('return_notes')
                            ->label('Notes sur le retour')
                            ->rows(2)
                            ->visible(fn($get) => $get('has_returned'))
                            ->placeholder('Ex: Retour anticipé, prolongation maladie, etc.'),

                        Forms\Components\Placeholder::make('return_info')
                            ->label('Informations')
                            ->content(function ($record) {
                                if (!$record || !$record->has_returned) {
                                    return '';
                                }

                                $info = "Retour confirmé le " . $record->return_confirmed_at?->format('d/m/Y à H:i');
                                if ($record->returnConfirmedBy) {
                                    $info .= " par " . $record->returnConfirmedBy->name;
                                }

                                if ($record->is_late_return) {
                                    $info .= "\n⚠️ Retour en retard de {$record->late_days} jour(s)";
                                }

                                return $info;
                            })
                            ->visible(fn($record) => $record && $record->has_returned),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record && $record->status === 'approved')
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Type de congé')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Jours')
                    ->suffix(' j')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Score')
                    ->badge()
                    ->color('success')
                    ->suffix(' pts')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved_n1',
                        'primary' => 'approved_n2',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'secondary' => 'cancelled',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'En attente',
                        'approved_n1' => 'Approuvé N1',
                        'approved_n2' => 'Approuvé N2',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'cancelled' => 'Annulé',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('has_returned')
                    ->label('Retour')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn($record) => $record->has_returned
                        ? 'Retour confirmé le ' . $record->return_confirmed_at?->format('d/m/Y')
                        : 'En attente de retour')
                    ->visible(fn($record) => $record && $record->status === 'approved'),

                Tables\Columns\BadgeColumn::make('return_status')
                    ->label('Statut Retour')
                    ->formatStateUsing(function ($record) {
                        if ($record->status !== 'approved') {
                            return '—';
                        }

                        if ($record->has_returned) {
                            return $record->is_late_return ? '⚠️ Retard' : '✅ À temps';
                        }

                        if (now()->isAfter($record->end_date)) {
                            return '❌ En retard';
                        }

                        return '⏳ En cours';
                    })
                    ->colors([
                        'success' => fn($record) => $record->has_returned && !$record->is_late_return,
                        'warning' => fn($record) => $record->is_late_return,
                        'danger' => fn($record) => !$record->has_returned && now()->isAfter($record->end_date),
                        'gray' => fn($record) => $record->status !== 'approved',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label('Type de congé')
                    ->relationship('leaveType', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved_n1' => 'Approuvé N1',
                        'approved_n2' => 'Approuvé N2',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'cancelled' => 'Annulé',
                    ]),

                Tables\Filters\Filter::make('my_team')
                    ->label('Mon équipe')
                    ->query(function ($query) {
                        // Filtrer les demandes de l'équipe de l'utilisateur connecté
                        // À adapter selon votre logique métier
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),

                Tables\Actions\Action::make('approve_n1')
                    ->label('Approuver N1')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved_n1',
                            'approved_by_n1' => auth()->id(),
                            'approved_at_n1' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Demande approuvée niveau 1')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('approve_n2')
                    ->label('Approuver N2')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'approved_n1')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved_n2',
                            'approved_by_n2' => auth()->id(),
                            'approved_at_n2' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Demande approuvée niveau 2')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('approve_final')
                    ->label('Approuver Final')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'approved_n2')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                        ]);

                        // Mettre à jour le solde de congés
                        $this->updateLeaveBalance($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Demande approuvée et solde mis à jour')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'approved_n1', 'approved_n2']))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Demande rejetée')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('confirm_return')
                    ->label('Confirmer Retour')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record && $record->status === 'approved' && !$record->has_returned)
                    ->form([
                        Forms\Components\DatePicker::make('actual_return_date')
                            ->label('Date de retour effective')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Textarea::make('return_notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Observations éventuelles sur le retour...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->confirmReturn($data['actual_return_date'], $data['return_notes'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->title('Retour confirmé')
                            ->success()
                            ->body("Le retour de {$record->employee->full_name} a été enregistré.")
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    protected static function updateLeaveBalance($leave)
    {
        $year = $leave->start_date->year;

        $balance = \App\Models\LeaveBalance::firstOrCreate(
            [
                'employee_id' => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'year' => $year,
            ],
            [
                'total_entitled' => $leave->leaveType->default_days,
                'used' => 0,
                'pending' => 0,
                'available' => $leave->leaveType->default_days,
            ]
        );

        $balance->used += $leave->total_days;
        $balance->recalculate();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Demande de Congé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Demandes de Congés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏖️ Gestion des Congés';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationLabel(): string
    {
        return 'Demandes de Congés';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
