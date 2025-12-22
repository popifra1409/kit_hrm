<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerformanceEvaluationResource\Pages;
use App\Models\PerformanceEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PerformanceEvaluationResource extends Resource
{
    protected static ?string $model = PerformanceEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static function getModelLabel(): string
    {
        return 'Évaluation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Évaluations de Performance';
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
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé Évalué')
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

                        Forms\Components\Select::make('evaluator_id')
                            ->label('Évaluateur')
                            ->relationship('evaluator', 'name')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->native(false)
                            ->default(auth()->id()),

                        Forms\Components\Select::make('period_type')
                            ->label('Type de Période')
                            ->options([
                                'monthly' => 'Mensuelle',
                                'quarterly' => 'Trimestrielle',
                                'semi_annual' => 'Semestrielle',
                                'annual' => 'Annuelle',
                            ])
                            ->default('annual')
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('evaluation_date')
                            ->label('Date d\'Évaluation')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('period_start_date')
                            ->label('Début de Période')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('period_end_date')
                            ->label('Fin de Période')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('period_start_date'),
                    ])
                    ->columns(3),

                Forms\Components\Tabs::make('Critères')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Compétences')
                            ->schema([
                                Forms\Components\TextInput::make('technical_skills')
                                    ->label('Compétences Techniques')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Maîtrise des outils et connaissances métier'),

                                Forms\Components\TextInput::make('soft_skills')
                                    ->label('Compétences Relationnelles')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Communication, empathie, relation client'),

                                Forms\Components\TextInput::make('leadership')
                                    ->label('Leadership')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Capacité à diriger et motiver (si applicable)'),

                                Forms\Components\TextInput::make('adaptability')
                                    ->label('Adaptabilité')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Capacité à s\'adapter aux changements'),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Performance')
                            ->schema([
                                Forms\Components\TextInput::make('productivity')
                                    ->label('Productivité')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Volume et rapidité de travail'),

                                Forms\Components\TextInput::make('quality_of_work')
                                    ->label('Qualité du Travail')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Précision, rigueur, respect des normes'),

                                Forms\Components\TextInput::make('initiative')
                                    ->label('Initiative / Autonomie')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Proactivité et capacité à décider'),

                                Forms\Components\TextInput::make('teamwork')
                                    ->label('Esprit d\'Équipe')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Collaboration et entraide'),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Comportement')
                            ->schema([
                                Forms\Components\TextInput::make('punctuality')
                                    ->label('Ponctualité / Assiduité')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.5)
                                    ->suffix('/ 5')
                                    ->helperText('Respect des horaires et présence'),

                                Forms\Components\Placeholder::make('overall_score')
                                    ->label('Score Global')
                                    ->content(fn($record) => $record ? number_format($record->overall_score, 2) . ' / 5' : 'Non calculé'),

                                Forms\Components\Placeholder::make('rating')
                                    ->label('Mention')
                                    ->content(fn($record) => $record ? $record->getRatingLabel() : 'Non définie'),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Analyse Qualitative')
                    ->schema([
                        Forms\Components\Textarea::make('strengths')
                            ->label('Points Forts')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Lister les principales qualités et réussites...'),

                        Forms\Components\Textarea::make('areas_for_improvement')
                            ->label('Axes d\'Amélioration')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Lister les points à travailler...'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Objectifs')
                    ->schema([
                        Forms\Components\Textarea::make('previous_objectives_review')
                            ->label('Bilan des Objectifs Précédents')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Faire le point sur les objectifs fixés précédemment...'),

                        Forms\Components\Textarea::make('new_objectives')
                            ->label('Nouveaux Objectifs')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Définir les objectifs pour la période suivante...'),

                        Forms\Components\Textarea::make('training_recommendations')
                            ->label('Formations Recommandées')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Suggérer des formations pour développer les compétences...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Commentaires')
                    ->schema([
                        Forms\Components\Textarea::make('evaluator_comments')
                            ->label('Commentaires de l\'Évaluateur')
                            ->rows(3)
                            ->maxLength(65535),

                        Forms\Components\Textarea::make('employee_comments')
                            ->label('Auto-Évaluation / Commentaires Employé')
                            ->rows(3)
                            ->maxLength(65535),

                        Forms\Components\Textarea::make('validator_comments')
                            ->label('Commentaires du Validateur (DRH/DG)')
                            ->rows(3)
                            ->maxLength(65535)
                            ->visible(fn($context) => $context === 'edit'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Statut et Validation')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'pending_employee' => 'En attente employé',
                                'pending_validator' => 'En attente validation',
                                'validated' => 'Validée',
                                'contested' => 'Contestée',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('validator_id')
                            ->label('Validateur')
                            ->relationship('validator', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
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

                Tables\Columns\BadgeColumn::make('period_type')
                    ->label('Période')
                    ->formatStateUsing(fn($record) => $record->getPeriodTypeLabel()),

                Tables\Columns\TextColumn::make('evaluation_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' / 5')
                    ->sortable()
                    ->color(fn($record) => $record->getRatingColor()),

                Tables\Columns\BadgeColumn::make('rating')
                    ->label('Mention')
                    ->formatStateUsing(fn($record) => $record->getRatingLabel())
                    ->color(fn($record) => $record->getRatingColor()),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->colors([
                        'gray' => 'draft',
                        'warning' => fn($state) => in_array($state, ['pending_employee', 'pending_validator']),
                        'success' => 'validated',
                        'danger' => 'contested',
                    ]),

                Tables\Columns\TextColumn::make('evaluator.name')
                    ->label('Évaluateur')
                    ->toggleable(),
            ])
            ->defaultSort('evaluation_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'pending_employee' => 'En attente employé',
                        'pending_validator' => 'En attente validation',
                        'validated' => 'Validée',
                        'contested' => 'Contestée',
                    ]),

                Tables\Filters\SelectFilter::make('rating')
                    ->label('Mention')
                    ->options([
                        'excellent' => 'Excellent',
                        'very_good' => 'Très Bon',
                        'good' => 'Bon',
                        'satisfactory' => 'Satisfaisant',
                        'needs_improvement' => 'À Améliorer',
                    ]),

                Tables\Filters\SelectFilter::make('period_type')
                    ->label('Type de Période')
                    ->options([
                        'monthly' => 'Mensuelle',
                        'quarterly' => 'Trimestrielle',
                        'semi_annual' => 'Semestrielle',
                        'annual' => 'Annuelle',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('sign_evaluator')
                    ->label('Signer (Évaluateur)')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'draft' && auth()->id() === $record->evaluator_id)
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->signByEvaluator())
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Évaluation signée')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('sign_employee')
                    ->label('Signer (Employé)')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn($record) => $record->status === 'pending_employee')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->signByEmployee()),

                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending_validator')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->validateByValidator()),

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
            'index' => Pages\ListPerformanceEvaluations::route('/'),
            'create' => Pages\CreatePerformanceEvaluation::route('/create'),
            'edit' => Pages\EditPerformanceEvaluation::route('/{record}/edit'),
        ];
    }
}
