<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotpartDeductionTypeResource\Pages;
use App\Models\QuotpartDeductionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuotpartDeductionTypeResource extends Resource
{
    protected static ?string $model = QuotpartDeductionType::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = '💰 Quote-Parts';
    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Type de Retenue';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Types de Retenues';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ex: CNPS, IRPP, CRTV')
                                    ->helperText('Identifiant unique'),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->placeholder('Ex: Cotisation CNPS')
                                    ->columnSpan(2),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Description de la retenue...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Type de Calcul')
                    ->schema([
                        Forms\Components\Select::make('calculation_type')
                            ->label('Méthode de Calcul')
                            ->options([
                                'percentage' => '📊 Pourcentage',
                                'fixed' => '💵 Montant Fixe',
                                'progressive' => '📈 Barème Progressif',
                            ])
                            ->required()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Réinitialiser les champs selon le type
                                if ($state === 'percentage') {
                                    $set('fixed_amount', null);
                                    $set('progressive_brackets', null);
                                } elseif ($state === 'fixed') {
                                    $set('rate', null);
                                    $set('progressive_brackets', null);
                                } elseif ($state === 'progressive') {
                                    $set('rate', null);
                                    $set('fixed_amount', null);
                                }
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('rate')
                                    ->label('Taux (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->visible(fn(callable $get) => $get('calculation_type') === 'percentage'),

                                Forms\Components\TextInput::make('fixed_amount')
                                    ->label('Montant Fixe')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->visible(fn(callable $get) => $get('calculation_type') === 'fixed'),
                            ]),

                        Forms\Components\Textarea::make('progressive_brackets')
                            ->label('Barème Progressif (JSON)')
                            ->rows(8)
                            ->placeholder('[
  {"min": 0, "max": 2000000, "rate": 10},
  {"min": 2000001, "max": 3000000, "rate": 15},
  {"min": 3000001, "max": 5000000, "rate": 25},
  {"min": 5000001, "max": null, "rate": 35}
]')
                            ->helperText('Format JSON : [{min, max, rate}]. max=null pour la tranche supérieure.')
                            ->visible(fn(callable $get) => $get('calculation_type') === 'progressive')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->label('Ordre d\'Application')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Ordre de calcul des retenues'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Actif')
                                    ->default(true)
                                    ->inline(false),
                            ]),
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

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('calculation_type')
                    ->label('Type de Calcul')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'percentage' => '📊 Pourcentage',
                        'fixed' => '💵 Fixe',
                        'progressive' => '📈 Progressif',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'percentage',
                        'success' => 'fixed',
                        'warning' => 'progressive',
                    ]),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Taux')
                    ->suffix('%')
                    ->sortable()
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('fixed_amount')
                    ->label('Montant Fixe')
                    ->money('XAF')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('calculation_type')
                    ->label('Type de Calcul')
                    ->options([
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Montant Fixe',
                        'progressive' => 'Barème Progressif',
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
            'index' => Pages\ListQuotpartDeductionTypes::route('/'),
            'create' => Pages\CreateQuotpartDeductionType::route('/create'),
            'edit' => Pages\EditQuotpartDeductionType::route('/{record}/edit'),
        ];
    }
}
