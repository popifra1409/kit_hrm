<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveDecisionResource\Pages;
use App\Models\LeaveDecision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveDecisionResource extends Resource
{
    protected static ?string $model = LeaveDecision::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('decision_number')
                            ->label('Numéro de Décision')
                            ->disabled()
                            ->placeholder('Généré automatiquement'),

                        Forms\Components\DatePicker::make('decision_date')
                            ->label('Date de la Décision')
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Select::make('leave_type')
                            ->label('Type de Congé')
                            ->options([
                                'conge' => 'Congé Annuel',
                                'permission' => 'Permission',
                                'autre' => 'Autre',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Période de Congé')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de Début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de Fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $start = $get('start_date');
                                if ($start && $state) {
                                    $days = \Carbon\Carbon::parse($start)->diffInDays($state) + 1;
                                    $set('duration_days', $days);
                                }
                            }),

                        Forms\Components\TextInput::make('duration_days')
                            ->label('Nombre de Jours')
                            ->numeric()
                            ->disabled()
                            ->suffix('jours'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description/Motif')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Document Signé')
                    ->schema([
                        Forms\Components\FileUpload::make('decision_document_path')
                            ->label('Décision Signée (PDF)')
                            ->directory('leave-decisions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->required()
                            ->helperText('Décision officielle signée par le DG'),
                    ]),

                Forms\Components\Section::make('Signature')
                    ->schema([
                        Forms\Components\Select::make('signed_by')
                            ->label('Signé par (DG)')
                            ->relationship('signedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('signed_at')
                            ->label('Date/Heure de Signature')
                            ->disabled()
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => '📝 Brouillon',
                                'signed' => '✅ Signé',
                                'used' => '✓ Utilisé (Congé créé)',
                                'archived' => '📋 Archivé',
                            ])
                            ->default('draft')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('decision_number')
                    ->label('Numéro')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(),

                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Du')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Au')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Jours')
                    ->suffix(' j'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'signed',
                        'info' => 'used',
                        'gray' => 'archived',
                    ]),

                Tables\Columns\TextColumn::make('signedBy.name')
                    ->label('Signé par'),
            ])
            ->actions([
                Tables\Actions\Action::make('sign')
                    ->label('Signer')
                    ->icon('heroicon-o-pencil')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->sign(auth()->user());
                        \Filament\Notifications\Notification::make()
                            ->title('Décision signée')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveDecisions::route('/'),
            'create' => Pages\CreateLeaveDecision::route('/create'),
            'edit' => Pages\EditLeaveDecision::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏖️ Congés & Absences';
    }

    public static function getNavigationSort(): ?int
    {
        return 0;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return 'Décisions de Mise en Congé';
    }

    public static function getModelLabel(): string
    {
        return 'Décision de Mise en Congé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Décisions de Mise en Congé';
    }
}
