<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DirectionResource\Pages;
use App\Models\Direction;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DirectionResource extends Resource
{
    protected static ?string $model = Direction::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function getModelLabel(): string
    {
        return 'Direction';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Directions';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la Direction')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Direction des Ressources Humaines')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('DRH')
                            ->helperText('Code unique pour identifier la direction'),

                        Forms\Components\TextInput::make('acronym')
                            ->label('Sigle/Acronyme')
                            ->maxLength(20)
                            ->placeholder('DRH'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Responsabilité')
                    ->schema([
                        Forms\Components\Select::make('director_id')
                            ->label('Directeur')
                            ->relationship('director', 'matricule')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(
                                fn($record) =>
                                $record->full_name . ' (' . $record->matricule . ') - ' . ($record->position?->name ?? 'N/A')
                            )
                            ->helperText('Sélectionnez le Directeur responsable')
                            ->columnSpan(2),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'administrative' => '🏢 Administrative',
                                'technique' => '⚙️ Technique',
                                'support' => '🛠️ Support',
                            ])
                            ->default('administrative')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Missions et attributions de la direction...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Contact & Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('location')
                            ->label('Localisation')
                            ->maxLength(255)
                            ->placeholder('Bâtiment A, 2ème étage'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->helperText('Ordre de tri dans les listes'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Direction')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'administrative' => '🏢 Administrative',
                        'technique' => '⚙️ Technique',
                        'support' => '🛠️ Support',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'administrative',
                        'warning' => 'technique',
                        'success' => 'support',
                    ]),

                Tables\Columns\TextColumn::make('director.full_name')
                    ->label('Directeur')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sub_directions_count')
                    ->label('Sous-Directions')
                    ->counts('subDirections')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-o-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'administrative' => 'Administrative',
                        'technique' => 'Technique',
                        'support' => 'Support',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('Toutes')
                    ->trueLabel('Actives uniquement')
                    ->falseLabel('Inactives uniquement'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
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
            'index' => Pages\ListDirections::route('/'),
            'create' => Pages\CreateDirection::route('/create'),
            'edit' => Pages\EditDirection::route('/{record}/edit'),
        ];
    }
}
