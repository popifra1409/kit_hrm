<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractTypeResource\Pages;
use App\Models\ContractType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractTypeResource extends Resource
{
    protected static ?string $model = ContractType::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = '📋 Contrats & Affectations';
    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return 'Type de Contrat';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Types de Contrats';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: CDI, CDD, Stage')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('Ex: CDI, CDD, STG')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Description du type de contrat...')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('requires_end_date')
                            ->label('Nécessite une date de fin')
                            ->default(false)
                            ->inline(false)
                            ->reactive(),

                        Forms\Components\TextInput::make('max_duration_months')
                            ->label('Durée maximale (mois)')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->visible(fn(callable $get) => $get('requires_end_date')),

                        Forms\Components\Toggle::make('renewable')
                            ->label('Renouvelable')
                            ->default(false)
                            ->inline(false)
                            ->visible(fn(callable $get) => $get('requires_end_date')),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employés')
                    ->counts('employees')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('requires_end_date')
                    ->label('Date fin requise')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('max_duration_months')
                    ->label('Durée max.')
                    ->suffix(' mois')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('renewable')
                    ->label('Renouvelable')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Inactifs uniquement'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractTypes::route('/'),
            'create' => Pages\CreateContractType::route('/create'),
            'edit' => Pages\EditContractType::route('/{record}/edit'),
        ];
    }
}
