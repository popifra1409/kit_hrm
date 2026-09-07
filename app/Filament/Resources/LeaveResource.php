<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Models\Leave;
use App\Models\LeaveDecision;
use App\Models\Employee;
use App\Models\Replacement;
use App\Services\LeaveEntitlementService;
use App\Services\LeaveWorkflowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employé & Type de Congé')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(fn() => Employee::where('is_active', true)->get()->pluck('full_name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->columnSpan(2),

                        Forms\Components\Placeholder::make('employee_info')
                            ->label('Matricule / Qualité')
                            ->content(function ($get) {
                                $employee = Employee::find($get('employee_id'));
                                if (!$employee) {
                                    return '—';
                                }
                                return $employee->matricule
                                    . ($employee->matricule_fonction_publique ? ' / FP: ' . $employee->matricule_fonction_publique : '')
                                    . ' — ' . $employee->administrative_status_label;
                            }),

                        Forms\Components\Select::make('leave_type_id')
                            ->label('Type de congé')
                            ->relationship('leaveType', 'name')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->native(false),

                        Forms\Components\Placeholder::make('balance_info')
                            ->label('Solde disponible (informatif)')
                            ->content(function ($get) {
                                $employee = Employee::find($get('employee_id'));
                                $leaveType = \App\Models\LeaveType::find($get('leave_type_id'));

                                if (!$employee || !$leaveType) {
                                    return '—';
                                }

                                $service = app(LeaveEntitlementService::class);

                                if (!$service->isEligibleForLeave($employee)) {
                                    $date = $service->getNextEligibilityDate($employee);
                                    return "⚠️ Pas encore éligible (1er congé possible à partir du " . $date?->format('d/m/Y') . ")";
                                }

                                $balance = $service->getAvailableDays($employee, $leaveType);

                                if ($balance === null) {
                                    return 'Pas de solde applicable pour ce type (événementiel).';
                                }

                                return "{$balance['available']} jour(s) disponible(s) sur {$balance['entitlement']} (cycle en cours).";
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Période')
                    ->description('Cochez "Fractionner" pour scinder ce congé en 2 prises (ex: 10 jours puis 8 jours plus tard) au sein de la même demande.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Date de début (1ère prise)')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->reactive(),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Date de fin (1ère prise)')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->reactive()
                                    ->afterOrEqual('start_date'),

                                Forms\Components\Placeholder::make('computed_days_1')
                                    ->label('Jours (1ère prise)')
                                    ->content(function ($get) {
                                        $start = $get('start_date');
                                        $end = $get('end_date');
                                        if (!$start || !$end) {
                                            return '—';
                                        }
                                        return Leave::countLeaveDays(\Carbon\Carbon::parse($start), \Carbon\Carbon::parse($end)) . ' jour(s)';
                                    }),
                            ]),

                        Forms\Components\Toggle::make('is_split')
                            ->label('Fractionner ce congé en 2 prises')
                            ->reactive()
                            ->default(false)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date_2')
                                    ->label('Date de début (2ème prise)')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->reactive()
                                    ->required(fn($get) => $get('is_split'))
                                    ->afterOrEqual('end_date')
                                    ->helperText('Doit être après la 1ère prise'),

                                Forms\Components\DatePicker::make('end_date_2')
                                    ->label('Date de fin (2ème prise)')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->reactive()
                                    ->required(fn($get) => $get('is_split'))
                                    ->afterOrEqual('start_date_2'),

                                Forms\Components\Placeholder::make('computed_days_2')
                                    ->label('Jours (2ème prise)')
                                    ->content(function ($get) {
                                        $start = $get('start_date_2');
                                        $end = $get('end_date_2');
                                        if (!$start || !$end) {
                                            return '—';
                                        }
                                        return Leave::countLeaveDays(\Carbon\Carbon::parse($start), \Carbon\Carbon::parse($end)) . ' jour(s)';
                                    }),
                            ])
                            ->visible(fn($get) => $get('is_split')),

                        Forms\Components\Placeholder::make('computed_days_total')
                            ->label('Total cumulé')
                            ->content(function ($get) {
                                $days = 0;
                                if ($get('start_date') && $get('end_date')) {
                                    $days += Leave::countLeaveDays(\Carbon\Carbon::parse($get('start_date')), \Carbon\Carbon::parse($get('end_date')));
                                }
                                if ($get('is_split') && $get('start_date_2') && $get('end_date_2')) {
                                    $days += Leave::countLeaveDays(\Carbon\Carbon::parse($get('start_date_2')), \Carbon\Carbon::parse($get('end_date_2')));
                                }
                                return $days . ' jour(s) au total';
                            })
                            ->extraAttributes(['class' => 'font-bold'])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Détails Spécifiques')
                    ->schema([
                        Forms\Components\TextInput::make('destination')
                            ->label('Destination')
                            ->maxLength(255)
                            ->visible(fn($get) => optional(\App\Models\LeaveType::find($get('leave_type_id')))->code === 'PERM'),

                        Forms\Components\TextInput::make('address_during_leave')
                            ->label('Adresse pendant le congé')
                            ->maxLength(255)
                            ->visible(fn($get) => optional(\App\Models\LeaveType::find($get('leave_type_id')))->code !== 'PERM'),

                        Forms\Components\TextInput::make('children_under_6_at_request')
                            ->label('Enfants légitimes < 6 ans (femme salariée)')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn($get) => in_array(
                                optional(\App\Models\LeaveType::find($get('leave_type_id')))->code,
                                ['CA', 'CMAT']
                            )),

                        Forms\Components\Select::make('replacement_id')
                            ->label('Intérimaire durant le congé')
                            ->relationship('replacement', 'id')
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->replacementEmployee?->full_name ?? '—')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Select::make('replacement_employee_id')
                                    ->label('Employé intérimaire')
                                    ->options(fn() => Employee::where('is_active', true)->get()->pluck('full_name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Début')
                                    ->required()
                                    ->native(false),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fin')
                                    ->required()
                                    ->native(false),
                            ])
                            ->createOptionUsing(function (array $data, $get) {
                                return Replacement::create([
                                    'original_employee_id' => $get('employee_id'),
                                    'replacement_employee_id' => $data['replacement_employee_id'],
                                    'start_date' => $data['start_date'],
                                    'end_date' => $data['end_date'],
                                    'reason' => 'leave',
                                    'status' => 'approved',
                                    'is_active' => true,
                                ])->id;
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Justification')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de la demande')
                            ->required()
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document_path')
                            ->label('Document Justificatif')
                            ->directory('leave-documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->required(fn($get) => optional(\App\Models\LeaveType::find($get('leave_type_id')))->requires_document)
                            ->helperText('Au choix, selon ce qui est disponible : décision de départ en congé déjà signée par la hiérarchie/DG, planning de congé du service, ou tout autre document attestant du droit au congé. PDF ou image, max 5 Mo.'),
                    ]),

                Forms\Components\Section::make('Circuit de Validation')
                    ->schema([
                        Forms\Components\Placeholder::make('workflow_status')
                            ->label('Statut')
                            ->content(function ($record) {
                                if (!$record) {
                                    return "Le circuit de validation démarre automatiquement à la création de la demande.";
                                }

                                $status = match ($record->status) {
                                    'pending' => '⏳ En attente — étape : ' . ($record->currentApprovalStep?->name ?? '—'),
                                    'approved' => '✅ Approuvée définitivement',
                                    'rejected' => '❌ Rejetée : ' . $record->rejection_reason,
                                    default => $record->status,
                                };

                                return $status;
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => $record !== null)
                    ->collapsible(),

                Forms\Components\Section::make('Décision de Congé (établie après validation)')
                    ->description('Une fois la demande approuvée par toutes les étapes du circuit, la décision de départ en congé est établie manuellement et soumise à la signature du DG. Renseignez-la ici une fois disponible.')
                    ->schema([
                        Forms\Components\Select::make('leave_decision_id')
                            ->label('Décision Signée (DG)')
                            ->relationship('leaveDecision', 'decision_number')
                            ->searchable()
                            ->preload()
                            ->helperText('Optionnel tant que la décision n\'a pas encore été établie/signée'),

                        Forms\Components\Placeholder::make('decision_info')
                            ->label('Informations')
                            ->content(function ($get) {
                                $decision = LeaveDecision::find($get('leave_decision_id'));
                                if (!$decision) {
                                    return 'Aucune décision liée pour le moment.';
                                }
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="p-3 bg-blue-50 rounded-lg">
                                        <p><strong>Période :</strong> ' . $decision->start_date->format('d/m/Y') . ' au ' . $decision->end_date->format('d/m/Y') . '</p>
                                        <p><strong>Durée :</strong> ' . $decision->duration_days . ' jours</p>
                                        <p><a href="' . \Storage::url($decision->decision_document_path) . '" class="text-blue-600 underline" target="_blank">📄 Voir la décision signée</a></p>
                                    </div>'
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => $record && $record->status === 'approved')
                    ->collapsed(),

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
                            ->visible(fn($get) => $get('has_returned')),
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
                    ->sortable()
                    ->description(fn(Leave $record) => $record->is_split ? '✂️ Fractionné en 2' : null),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('currentApprovalStep.name')
                    ->label('Étape en cours')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->visible(fn($record) => !$record || $record->status === 'pending'),

                Tables\Columns\IconColumn::make('has_returned')
                    ->label('Retour')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),

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
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                    ]),

                Tables\Filters\SelectFilter::make('current_approval_step_id')
                    ->label('Étape en cours')
                    ->relationship('currentApprovalStep', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Voir'),
                    Tables\Actions\EditAction::make()->label('Modifier'),

                    Tables\Actions\Action::make('approve_step')
                        ->label('Approuver l\'étape')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn(Leave $record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalDescription(fn(Leave $record) => 'Approuver l\'étape "' . $record->currentApprovalStep?->name . '" ?')
                        ->form([
                            Forms\Components\Textarea::make('comments')
                                ->label('Commentaire (optionnel)'),
                        ])
                        ->action(function (Leave $record, array $data) {
                            app(LeaveWorkflowService::class)->approveCurrentStep($record, auth()->user(), $data['comments'] ?? null);

                            \Filament\Notifications\Notification::make()
                                ->title('Étape approuvée')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reject_step')
                        ->label('Rejeter')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn(Leave $record) => $record->status === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motif du rejet')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Leave $record, array $data) {
                            app(LeaveWorkflowService::class)->rejectCurrentStep($record, auth()->user(), $data['reason']);

                            \Filament\Notifications\Notification::make()
                                ->title('Demande rejetée')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('confirm_return')
                        ->label('Confirmer Retour')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(Leave $record) => $record->status === 'approved' && !$record->has_returned)
                        ->form([
                            Forms\Components\DatePicker::make('actual_return_date')
                                ->label('Date de retour effective')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\Textarea::make('return_notes')
                                ->label('Notes')
                                ->rows(3),
                        ])
                        ->action(function (Leave $record, array $data) {
                            $record->confirmReturn($data['actual_return_date'], $data['return_notes'] ?? null);

                            \Filament\Notifications\Notification::make()
                                ->title('Retour confirmé')
                                ->success()
                                ->body("Le retour de {$record->employee->full_name} a été enregistré.")
                                ->send();
                        }),
                ])
                    ->button()
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal'),
            ], position: ActionsPosition::BeforeColumns)
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

    public static function getNavigationLabel(): string
    {
        return 'Demandes de Congés';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Demandes de Congés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏖️ Congés & Absences';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
