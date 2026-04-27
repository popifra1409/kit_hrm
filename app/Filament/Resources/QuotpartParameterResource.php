<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotpartParameterResource\Pages;
use App\Models\QuotpartParameter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuotpartParameterResource extends Resource
{
    protected static ?string $model = QuotpartParameter::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = '💰 Quote-Parts';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Paramètre de Calcul';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Paramètres de Calcul';
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
                                        'base' => '📊 Base (Indice, Ancienneté)',
                                        'performance' => '🎯 Performance (Évaluation)',
                                        'medical' => '🩺 Activités Médicales',
                                        'management' => '👔 Responsabilités',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->reactive(),

                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ex: indice_weight, garde_weight')
                                    ->helperText('Identifiant unique du paramètre'),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du Paramètre')
                                    ->required()
                                    ->placeholder('Ex: Poids de l\'indice')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Description détaillée du paramètre...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('weight')
                                    ->label('Poids / Coefficient')
                                    ->numeric()
                                    ->required()
                                    ->default(1.0)
                                    ->step(0.1)
                                    ->minValue(0)
                                    ->helperText('Coefficient multiplicateur'),

                                Forms\Components\Select::make('applies_to')
                                    ->label('S\'applique à')
                                    ->options([
                                        'all' => '👥 Tous',
                                        'soignant' => '👨‍⚕️ Personnel Soignant',
                                        'non_soignant' => '💼 Personnel Non-Soignant',
                                        'paramedical' => '🩺 Personnel Paramédical',
                                        'management' => '👔 Personnel d\'Encadrement',
                                    ])
                                    ->default('all')
                                    ->native(false),

                                Forms\Components\TextInput::make('min_value')
                                    ->label('Valeur Minimale')
                                    ->numeric()
                                    ->nullable()
                                    ->step(0.01)
                                    ->helperText('Optionnel'),

                                Forms\Components\TextInput::make('max_value')
                                    ->label('Valeur Maximale')
                                    ->numeric()
                                    ->nullable()
                                    ->step(0.01)
                                    ->helperText('Optionnel'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->label('Ordre d\'Affichage')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Pour le tri dans les listes'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Actif')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Exemples d\'Utilisation')
                    ->schema([
                        Forms\Components\Placeholder::make('examples')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $category = $get('category');
                                $weight = $get('weight') ?? 1.0;

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="space-y-2">
                                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                            <p class="font-bold text-blue-900">Catégorie : ' . match ($category) {
                                        'base' => '📊 Base (Indice, Ancienneté)',
                                        'performance' => '🎯 Performance (Évaluation)',
                                        'medical' => '🩺 Activités Médicales',
                                        'management' => '👔 Responsabilités',
                                        default => 'Non définie'
                                    } . '</p>
                                            <p class="text-sm text-blue-700 mt-2">Poids actuel : <strong>' . number_format($weight, 2) . '</strong></p>
                                        </div>
                                        ' . match ($category) {
                                        'base' => '<div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                                <p class="text-sm text-green-700"><strong>Exemple :</strong> Si l\'indice d\'un employé est 500, les points obtenus = 500 × ' . $weight . ' = <strong>' . (500 * $weight) . ' points</strong></p>
                                            </div>',
                                        'performance' => '<div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                                <p class="text-sm text-yellow-700"><strong>Exemple :</strong> Si la note d\'évaluation est 18/20, les points obtenus = 18 × ' . $weight . ' = <strong>' . (18 * $weight) . ' points</strong></p>
                                            </div>',
                                        'medical' => '<div class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                                <p class="text-sm text-purple-700"><strong>Exemple :</strong> Si 5 gardes effectuées, les points obtenus = 5 × ' . $weight . ' = <strong>' . (5 * $weight) . ' points</strong></p>
                                            </div>',
                                        'management' => '<div class="p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                                                <p class="text-sm text-indigo-700"><strong>Exemple :</strong> Bonus de responsabilité = <strong>' . $weight . ' points</strong> ajoutés</p>
                                            </div>',
                                        default => ''
                                    } . '
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
                        'base' => '📊 Base',
                        'performance' => '🎯 Performance',
                        'medical' => '🩺 Médical',
                        'management' => '👔 Management',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'base',
                        'success' => 'performance',
                        'warning' => 'medical',
                        'info' => 'management',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Paramètre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('secondary')
                    ->copyable()
                    ->copyMessage('Code copié!')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Poids')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),

                Tables\Columns\BadgeColumn::make('applies_to')
                    ->label('S\'applique à')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'all' => '👥 Tous',
                        'soignant' => '👨‍⚕️ Soignant',
                        'non_soignant' => '💼 Non-Soignant',
                        'paramedical' => '🩺 Paramédical',
                        'management' => '👔 Encadrement',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'all',
                        'success' => 'soignant',
                        'info' => 'non_soignant',
                        'warning' => 'paramedical',
                        'danger' => 'management',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'base' => 'Base',
                        'performance' => 'Performance',
                        'medical' => 'Médical',
                        'management' => 'Management',
                    ]),

                Tables\Filters\SelectFilter::make('applies_to')
                    ->label('S\'applique à')
                    ->options([
                        'all' => 'Tous',
                        'soignant' => 'Personnel Soignant',
                        'non_soignant' => 'Personnel Non-Soignant',
                        'paramedical' => 'Personnel Paramédical',
                        'management' => 'Personnel d\'Encadrement',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListQuotpartParameters::route('/'),
            'create' => Pages\CreateQuotpartParameter::route('/create'),
            'edit' => Pages\EditQuotpartParameter::route('/{record}/edit'),
        ];
    }
}
