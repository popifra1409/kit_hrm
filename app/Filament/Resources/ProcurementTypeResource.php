<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcurementTypeResource\Pages;
use App\Models\ProcurementType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProcurementTypeResource extends Resource
{
    protected static ?string $model = ProcurementType::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getModelLabel(): string
    {
        return 'Type de Marché';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Types de Marchés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏗️ Marchés Publics';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de Base')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du Type')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ex: TRV, FRN, SVC'),

                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'works' => 'Travaux',
                                'goods' => 'Fournitures',
                                'services' => 'Services',
                                'consulting' => 'Conseils/Études',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Seuils ARMP (en FCFA)')
                    ->schema([
                        Forms\Components\TextInput::make('threshold_aon')
                            ->label('Seuil Appel d\'Offres National')
                            ->numeric()
                            ->prefix('FCFA')
                            ->helperText('Montant minimum pour un AON'),

                        Forms\Components\TextInput::make('threshold_aoi')
                            ->label('Seuil Appel d\'Offres International')
                            ->numeric()
                            ->prefix('FCFA')
                            ->helperText('Montant minimum pour un AOI'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('requires_armp_approval')
                            ->label('Approbation ARMP Requise')
                            ->default(false)
                            ->helperText('Ce type de marché nécessite une approbation ARMP'),

                        Forms\Components\TextInput::make('min_publication_days')
                            ->label('Délai Minimum de Publication (jours)')
                            ->numeric()
                            ->default(30)
                            ->minValue(0)
                            ->helperText('Nombre de jours minimum pour la publication'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->colors([
                        'primary' => 'works',
                        'success' => 'goods',
                        'info' => 'services',
                        'warning' => 'consulting',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'works' => 'Travaux',
                        'goods' => 'Fournitures',
                        'services' => 'Services',
                        'consulting' => 'Conseils',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('threshold_aon')
                    ->label('Seuil AON')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('threshold_aoi')
                    ->label('Seuil AOI')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\IconColumn::make('requires_armp_approval')
                    ->label('ARMP')
                    ->boolean(),

                Tables\Columns\TextColumn::make('min_publication_days')
                    ->label('Délai (j)')
                    ->suffix(' jours')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'works' => 'Travaux',
                        'goods' => 'Fournitures',
                        'services' => 'Services',
                        'consulting' => 'Conseils',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcurementTypes::route('/'),
            'create' => Pages\CreateProcurementType::route('/create'),
            'edit' => Pages\EditProcurementType::route('/{record}/edit'),
        ];
    }
}
