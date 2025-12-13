<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollItemResource\Pages;
use App\Models\PayrollItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollItemResource extends Resource
{
    protected static ?string $model = PayrollItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getModelLabel(): string
    {
        return 'Élément de Paie';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Éléments de Paie';
    }

    public static function getNavigationGroup(): ?string
    {
        return '💰 Gestion de la Paie';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Éléments de Paie';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de l\'élément')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ex: SALBASE, PRIMEANC, IRPP'),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'gain' => 'Gain (Salaire/Prime)',
                                'deduction' => 'Retenue',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'base' => 'Salaire de Base',
                                'prime' => 'Prime',
                                'indemnity' => 'Indemnité',
                                'tax' => 'Impôt',
                                'social' => 'Cotisation Sociale',
                                'other_deduction' => 'Autre Retenue',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Paramètres Fiscaux')
                    ->schema([
                        Forms\Components\Toggle::make('is_taxable')
                            ->label('Imposable (IRPP)')
                            ->default(true)
                            ->helperText('Cet élément entre dans le calcul du salaire imposable'),

                        Forms\Components\Toggle::make('is_subject_to_cnps')
                            ->label('Soumis à CNPS')
                            ->default(true)
                            ->helperText('Cet élément entre dans le calcul des cotisations CNPS'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Méthode de Calcul')
                    ->schema([
                        Forms\Components\Select::make('calculation_method')
                            ->label('Méthode de calcul')
                            ->options([
                                'fixed' => 'Montant Fixe',
                                'percentage' => 'Pourcentage',
                                'formula' => 'Formule Personnalisée',
                            ])
                            ->default('fixed')
                            ->required()
                            ->reactive()
                            ->native(false),

                        Forms\Components\TextInput::make('fixed_amount')
                            ->label('Montant Fixe')
                            ->numeric()
                            ->prefix('FCFA')
                            ->visible(fn($get) => $get('calculation_method') === 'fixed'),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Pourcentage')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1)
                            ->visible(fn($get) => $get('calculation_method') === 'percentage')
                            ->helperText('Ex: 20 pour 20% du salaire de base'),

                        Forms\Components\Textarea::make('formula')
                            ->label('Formule')
                            ->rows(3)
                            ->visible(fn($get) => $get('calculation_method') === 'formula')
                            ->helperText('Pour les calculs complexes (IRPP, etc.)'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Affichage')
                    ->schema([
                        Forms\Components\TextInput::make('display_order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->helperText('Plus le nombre est petit, plus l\'élément apparaît en haut'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'gain',
                        'danger' => 'deduction',
                    ])
                    ->formatStateUsing(
                        fn(string $state): string =>
                        $state === 'gain' ? 'Gain' : 'Retenue'
                    ),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->colors([
                        'primary' => 'base',
                        'success' => 'prime',
                        'info' => 'indemnity',
                        'warning' => 'tax',
                        'danger' => 'social',
                        'secondary' => 'other_deduction',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'base' => 'Salaire Base',
                        'prime' => 'Prime',
                        'indemnity' => 'Indemnité',
                        'tax' => 'Impôt',
                        'social' => 'Cotisation',
                        'other_deduction' => 'Autre',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_taxable')
                    ->label('Imposable')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_subject_to_cnps')
                    ->label('CNPS')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('display_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'gain' => 'Gain',
                        'deduction' => 'Retenue',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'base' => 'Salaire de Base',
                        'prime' => 'Prime',
                        'indemnity' => 'Indemnité',
                        'tax' => 'Impôt',
                        'social' => 'Cotisation Sociale',
                        'other_deduction' => 'Autre Retenue',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
            ])
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
            'index' => Pages\ListPayrollItems::route('/'),
            'create' => Pages\CreatePayrollItem::route('/create'),
            'edit' => Pages\EditPayrollItem::route('/{record}/edit'),
        ];
    }
}
