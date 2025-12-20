<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeAdvancementHistoryResource\Pages;
use App\Models\EmployeeAdvancementHistory;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeAdvancementHistoryResource extends Resource
{
    protected static ?string $model = EmployeeAdvancementHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public static function getModelLabel(): string
    {
        return 'Avancement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Historique des Avancements';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
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

                        Forms\Components\Select::make('advancement_type')
                            ->label('Type d\'Avancement')
                            ->options([
                                'echelon' => 'Avancement d\'Échelon',
                                'category' => 'Changement de Catégorie',
                                'grade' => 'Changement de Grade',
                                'salary_adjustment' => 'Ajustement Salarial',
                            ])
                            ->required()
                            ->reactive()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Ancienne Situation')
                    ->schema([
                        Forms\Components\TextInput::make('old_category')
                            ->label('Ancienne Catégorie')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->visible(fn($get) => in_array($get('advancement_type'), ['category'])),

                        Forms\Components\TextInput::make('old_echelon')
                            ->label('Ancien Échelon')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->visible(fn($get) => in_array($get('advancement_type'), ['echelon'])),

                        Forms\Components\TextInput::make('old_salary')
                            ->label('Ancien Salaire')
                            ->numeric()
                            ->prefix('FCFA')
                            ->visible(fn($get) => in_array($get('advancement_type'), ['salary_adjustment', 'echelon', 'category'])),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Nouvelle Situation')
                    ->schema([
                        Forms\Components\TextInput::make('new_category')
                            ->label('Nouvelle Catégorie')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(12)
                            ->visible(fn($get) => in_array($get('advancement_type'), ['category'])),

                        Forms\Components\TextInput::make('new_echelon')
                            ->label('Nouvel Échelon')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(12)
                            ->visible(fn($get) => in_array($get('advancement_type'), ['echelon'])),

                        Forms\Components\TextInput::make('new_salary')
                            ->label('Nouveau Salaire')
                            ->numeric()
                            ->required()
                            ->prefix('FCFA')
                            ->visible(fn($get) => in_array($get('advancement_type'), ['salary_adjustment', 'echelon', 'category'])),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Détails de l\'Avancement')
                    ->schema([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Date d\'Effet')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Toggle::make('is_automatic')
                            ->label('Avancement Automatique')
                            ->default(false)
                            ->helperText('Cochez si l\'avancement est automatique (ancienneté)'),

                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de l\'Avancement')
                            ->rows(2)
                            ->maxLength(65535)
                            ->placeholder('Ex: Ancienneté, mérite, promotion exceptionnelle...')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('decision_number')
                            ->label('Numéro de Décision')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('decision_date')
                            ->label('Date de la Décision')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\FileUpload::make('decision_document_path')
                            ->label('Document de Décision (PDF)')
                            ->directory('advancements/decisions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120)
                            ->columnSpanFull(),

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

                Tables\Columns\BadgeColumn::make('advancement_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'echelon',
                        'primary' => 'category',
                        'info' => 'grade',
                        'warning' => 'salary_adjustment',
                    ])
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
                        if ($record->advancement_type === 'category') {
                            return "Cat. {$record->old_category}";
                        }
                        return number_format($record->old_salary, 0, ',', ' ') . ' FCFA';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('new_echelon')
                    ->label('Nouveau')
                    ->formatStateUsing(function ($record) {
                        if ($record->advancement_type === 'echelon') {
                            return "Éch. {$record->new_echelon}";
                        }
                        if ($record->advancement_type === 'category') {
                            return "Cat. {$record->new_category}";
                        }
                        return number_format($record->new_salary, 0, ',', ' ') . ' FCFA';
                    })
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date d\'Effet')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_automatic')
                    ->label('Auto')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('decision_number')
                    ->label('N° Décision')
                    ->searchable()
                    ->toggleable(),

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

                Tables\Filters\SelectFilter::make('advancement_type')
                    ->label('Type')
                    ->options([
                        'echelon' => 'Échelon',
                        'category' => 'Catégorie',
                        'grade' => 'Grade',
                        'salary_adjustment' => 'Salaire',
                    ]),

                Tables\Filters\TernaryFilter::make('is_automatic')
                    ->label('Automatique')
                    ->placeholder('Tous')
                    ->trueLabel('Automatiques uniquement')
                    ->falseLabel('Exceptionnels uniquement'),
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
            'index' => Pages\ListEmployeeAdvancementHistories::route('/'),
            'create' => Pages\CreateEmployeeAdvancementHistory::route('/create'),
            'edit' => Pages\EditEmployeeAdvancementHistory::route('/{record}/edit'),
        ];
    }
}
