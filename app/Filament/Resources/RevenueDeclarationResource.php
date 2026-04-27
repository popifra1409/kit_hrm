<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RevenueDeclarationResource\Pages;
use App\Models\RevenueDeclaration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RevenueDeclarationResource extends Resource
{
    protected static ?string $model = RevenueDeclaration::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = '💰 Quote-Parts';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Déclaration de Recettes';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Déclarations de Recettes';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Période et Source')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('period_id')
                                    ->label('Période')
                                    ->relationship('period', 'code')
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        $record->code . ' - ' . $record->getMonthNameAttribute()
                                    )
                                    ->preload()
                                    ->reactive(),

                                Forms\Components\Select::make('source')
                                    ->label('Source de Recette')
                                    ->options([
                                        'consultations' => '🩺 Consultations',
                                        'hospitalisations' => '🏥 Hospitalisations',
                                        'pharmacie' => '💊 Pharmacie',
                                        'imagerie' => '📷 Imagerie',
                                        'laboratoire' => '🔬 Laboratoire',
                                        'urgences' => '🚑 Urgences',
                                        'chirurgie' => '🔪 Chirurgie',
                                        'autres' => '📦 Autres',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Montant')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant de la Recette')
                            ->numeric()
                            ->required()
                            ->prefix('FCFA')
                            ->placeholder('Ex: 5000000')
                            ->helperText('Montant total de la recette pour cette source'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description / Détails')
                            ->rows(3)
                            ->placeholder('Détails sur cette recette...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Déclaration')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('declared_by')
                                    ->label('Déclaré par')
                                    ->relationship('declarer', 'name')
                                    ->required()
                                    ->default(auth()->id())
                                    ->preload(),

                                Forms\Components\DateTimePicker::make('declared_at')
                                    ->label('Date de Déclaration')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->default(now()),
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
                Tables\Columns\TextColumn::make('period.code')
                    ->label('Période')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->description(
                        fn(RevenueDeclaration $record) =>
                        $record->period?->getMonthNameAttribute()
                    ),

                Tables\Columns\BadgeColumn::make('source')
                    ->label('Source')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'consultations' => '🩺 Consultations',
                        'hospitalisations' => '🏥 Hospitalisations',
                        'pharmacie' => '💊 Pharmacie',
                        'imagerie' => '📷 Imagerie',
                        'laboratoire' => '🔬 Laboratoire',
                        'urgences' => '🚑 Urgences',
                        'chirurgie' => '🔪 Chirurgie',
                        'autres' => '📦 Autres',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'consultations',
                        'success' => 'hospitalisations',
                        'warning' => 'pharmacie',
                        'info' => 'imagerie',
                        'danger' => 'laboratoire',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XAF')
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('declarer.name')
                    ->label('Déclaré par')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('declared_at')
                    ->label('Date de Déclaration')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period_id')
                    ->label('Période')
                    ->relationship('period', 'code'),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'consultations' => 'Consultations',
                        'hospitalisations' => 'Hospitalisations',
                        'pharmacie' => 'Pharmacie',
                        'imagerie' => 'Imagerie',
                        'laboratoire' => 'Laboratoire',
                        'urgences' => 'Urgences',
                        'chirurgie' => 'Chirurgie',
                        'autres' => 'Autres',
                    ]),
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
            ->defaultSort('declared_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRevenueDeclarations::route('/'),
            'create' => Pages\CreateRevenueDeclaration::route('/create'),
            'edit' => Pages\EditRevenueDeclaration::route('/{record}/edit'),
        ];
    }
}
