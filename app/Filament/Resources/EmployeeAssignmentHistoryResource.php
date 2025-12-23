<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeAssignmentHistoryResource\Pages;
use App\Models\EmployeeAssignmentHistory;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeAssignmentHistoryResource extends Resource
{
    protected static ?string $model = EmployeeAssignmentHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function getModelLabel(): string
    {
        return 'Affectation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Historique des Affectations';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employé')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->preload(),

                        Forms\Components\Select::make('assignment_type')
                            ->label('Type d\'Affectation')
                            ->options([
                                'position' => 'Changement de Poste',
                                'department' => 'Changement de Département',
                                'service' => 'Changement de Service',
                                'location' => 'Changement de Lieu',
                                'contract_type' => 'Changement de Type de Contrat',
                            ])
                            ->required()
                            ->reactive()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Ancienne Affectation')
                    ->schema([
                        Forms\Components\TextInput::make('old_position_name')
                            ->label('Ancien Poste')
                            ->maxLength(255)
                            ->visible(fn($get) => in_array($get('assignment_type'), ['position'])),

                        Forms\Components\Select::make('old_department_id')
                            ->label('Ancien Département')
                            ->relationship('oldDepartment', 'name')
                            ->searchable()
                            ->native(false)
                            ->visible(fn($get) => in_array($get('assignment_type'), ['department', 'service'])),

                        Forms\Components\Select::make('old_service_id')
                            ->label('Ancien Service')
                            ->relationship('oldService', 'name')
                            ->searchable()
                            ->native(false)
                            ->visible(fn($get) => $get('assignment_type') === 'service'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Nouvelle Affectation')
                    ->schema([
                        Forms\Components\TextInput::make('new_position_')
                            ->label('Nouveau Poste')
                            ->required()
                            ->maxLength(255)
                            ->visible(fn($get) => in_array($get('assignment_type'), ['position'])),

                        Forms\Components\Select::make('new_department_id')
                            ->label('Nouveau Département')
                            ->relationship('newDepartment', 'name')
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->visible(fn($get) => in_array($get('assignment_type'), ['department', 'service'])),

                        Forms\Components\Select::make('new_service_id')
                            ->label('Nouveau Service')
                            ->relationship('newService', 'name')
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->visible(fn($get) => $get('assignment_type') === 'service'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Détails de l\'Affectation')
                    ->schema([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Date d\'Effet')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de Fin (si temporaire)')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn($get) => $get('is_temporary')),

                        Forms\Components\Toggle::make('is_temporary')
                            ->label('Affectation Temporaire')
                            ->default(false)
                            ->reactive()
                            ->helperText('Cochez si l\'affectation est temporaire (remplacement, détachement)'),

                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de l\'Affectation')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('decision_number')
                            ->label('Numéro de Décision')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('decision_date')
                            ->label('Date de la Décision')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
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

                Tables\Columns\BadgeColumn::make('assignment_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'position',
                        'success' => 'department',
                        'info' => 'service',
                        'warning' => 'location',
                        'secondary' => 'contract_type',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'position' => 'Poste',
                        'department' => 'Département',
                        'service' => 'Service',
                        'location' => 'Lieu',
                        'contract_type' => 'Contrat',
                        default => $state,
                    }),

                // Colonne dynamique qui affiche selon le type
                Tables\Columns\TextColumn::make('ancien')
                    ->label('Ancien')
                    ->formatStateUsing(function ($record) {
                        if (!$record) return '—';

                        return match ($record->assignment_type) {
                            'position' => $record->old_position_name ?? '—',
                            'service' => $record->old_service_name ?? '—',
                            'department' => $record->old_department_name ?? '—',
                            default => '—',
                        };
                    })
                    ->searchable(false),

                Tables\Columns\TextColumn::make('nouveau')
                    ->label('Nouveau')
                    ->formatStateUsing(function ($record) {
                        if (!$record) return '—';

                        return match ($record->assignment_type) {
                            'position' => $record->new_position_name ?? '—',
                            'service' => $record->new_service_name ?? '—',
                            'department' => $record->new_department_name ?? '—',
                            default => '—',
                        };
                    })
                    ->weight('bold')
                    ->color('success')
                    ->searchable(false),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date d\'Effet')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_temporary')
                    ->label('Temporaire')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('decision_number')
                    ->label('N° Décision')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enregistré le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('effective_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'full_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assignment_type')
                    ->label('Type')
                    ->options([
                        'position' => 'Poste',
                        'department' => 'Département',
                        'service' => 'Service',
                        'location' => 'Lieu',
                        'contract_type' => 'Contrat',
                    ]),

                Tables\Filters\TernaryFilter::make('is_temporary')
                    ->label('Temporaire')
                    ->placeholder('Tous')
                    ->trueLabel('Temporaires uniquement')
                    ->falseLabel('Définitives uniquement'),
            ])
            ->actions([
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
            'index' => Pages\ListEmployeeAssignmentHistories::route('/'),
            'create' => Pages\CreateEmployeeAssignmentHistory::route('/create'),
            'edit' => Pages\EditEmployeeAssignmentHistory::route('/{record}/edit'),
        ];
    }
}
