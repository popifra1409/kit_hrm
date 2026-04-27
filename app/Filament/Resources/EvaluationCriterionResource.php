<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationCriterionResource\Pages;
use App\Models\EvaluationCriterion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvaluationCriterionResource extends Resource
{
    protected static ?string $model = EvaluationCriterion::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = '💰 Quote-Parts';
    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Critère d\'Évaluation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Critères d\'Évaluation';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Catégorie')
                                    ->options([
                                        'comportement' => '🎯 Comportement',
                                        'competence' => '⭐ Compétence',
                                        'performance' => '🚀 Performance',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ex: assiduite, discipline')
                                    ->helperText('Identifiant unique'),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du Critère')
                                    ->required()
                                    ->placeholder('Ex: Assiduité et Ponctualité')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Description détaillée du critère...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('max_score')
                                    ->label('Note Maximale')
                                    ->numeric()
                                    ->required()
                                    ->default(20)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('/ 100')
                                    ->helperText('Sur 20 généralement'),

                                Forms\Components\TextInput::make('weight')
                                    ->label('Poids/Coefficient')
                                    ->numeric()
                                    ->required()
                                    ->default(1.0)
                                    ->step(0.1)
                                    ->minValue(0)
                                    ->helperText('Importance dans le calcul'),

                                Forms\Components\Select::make('applies_to')
                                    ->label('S\'applique à')
                                    ->options([
                                        'all' => '👥 Tous',
                                        'soignant' => '👨‍⚕️ Personnel Soignant',
                                        'non_soignant' => '💼 Personnel Non-Soignant',
                                        'paramedical' => '🩺 Personnel Paramédical',
                                    ])
                                    ->default('all')
                                    ->native(false),

                                Forms\Components\TextInput::make('order')
                                    ->label('Ordre')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Ordre d\'affichage'),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Section::make('Aperçu du Calcul')
                    ->schema([
                        Forms\Components\Placeholder::make('calculation_preview')
                            ->label('Exemple de Calcul')
                            ->content(function (Forms\Get $get) {
                                $maxScore = $get('max_score') ?? 20;
                                $weight = $get('weight') ?? 1.0;

                                $exampleScore = 15; // Note exemple
                                $weighted = $exampleScore * $weight;

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p class="font-bold text-blue-900">Exemple de calcul :</p>
                                        <p class="text-sm text-blue-700 mt-2">
                                            Si un employé obtient <strong>15/' . $maxScore . '</strong> pour ce critère :
                                        </p>
                                        <p class="text-sm text-blue-700">
                                            Points obtenus = 15 × ' . $weight . ' = <strong>' . number_format($weighted, 2) . ' points</strong>
                                        </p>
                                    </div>'
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'comportement' => '🎯 Comportement',
                        'competence' => '⭐ Compétence',
                        'performance' => '🚀 Performance',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'comportement',
                        'success' => 'competence',
                        'warning' => 'performance',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Critère')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('max_score')
                    ->label('Note Max')
                    ->suffix('/100')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Poids')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('applies_to')
                    ->label('S\'applique à')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'all' => '👥 Tous',
                        'soignant' => '👨‍⚕️ Soignant',
                        'non_soignant' => '💼 Non-Soignant',
                        'paramedical' => '🩺 Paramédical',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'all',
                        'success' => 'soignant',
                        'info' => 'non_soignant',
                        'warning' => 'paramedical',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'comportement' => 'Comportement',
                        'competence' => 'Compétence',
                        'performance' => 'Performance',
                    ]),

                Tables\Filters\SelectFilter::make('applies_to')
                    ->label('S\'applique à')
                    ->options([
                        'all' => 'Tous',
                        'soignant' => 'Personnel Soignant',
                        'non_soignant' => 'Personnel Non-Soignant',
                        'paramedical' => 'Personnel Paramédical',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluationCriteria::route('/'),
            'create' => Pages\CreateEvaluationCriterion::route('/create'),
            'edit' => Pages\EditEvaluationCriterion::route('/{record}/edit'),
        ];
    }
}
