<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubDirectionResource\Pages;
use App\Models\SubDirection;
use App\Models\Direction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class SubDirectionResource extends Resource
{
    protected static ?string $model = SubDirection::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getModelLabel(): string
    {
        return 'Sous-Direction';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sous-Directions';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rattachement')
                    ->schema([
                        Forms\Components\Select::make('direction_id')
                            ->label('Direction')
                            ->relationship('direction', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText('Direction de rattachement')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la Sous-Direction')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Sous-Direction des Finances')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('SDF')
                            ->helperText('Code unique'),

                        Forms\Components\TextInput::make('acronym')
                            ->label('Sigle/Acronyme')
                            ->maxLength(20)
                            ->placeholder('SDF'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Responsabilité')
                    ->schema([
                        Forms\Components\Select::make('sub_director_id')
                            ->label('Sous-Directeur')
                            ->relationship('subDirector', 'matricule')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(
                                fn($record) =>
                                $record->full_name . ' (' . $record->matricule . ') - ' . ($record->jobTitle?->name ?? 'N/A')
                            )
                            ->helperText('Sélectionnez le Sous-Directeur responsable')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Missions et attributions...')
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
                            ->placeholder('Bâtiment B, Bureau 205'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0),

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
                Tables\Columns\TextColumn::make('direction.name')
                    ->label('Direction')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Sous-Direction')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('subDirector.full_name')
                    ->label('Sous-Directeur')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-o-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direction_id')
                    ->label('Direction')
                    ->relationship('direction', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Voir'),
                    Tables\Actions\EditAction::make()->label('Modifier'),
                    Tables\Actions\DeleteAction::make()->label('Supprimer'),
                ])
                    ->button()
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal'),
            ], position: ActionsPosition::BeforeColumns)
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
            'index' => Pages\ListSubDirections::route('/'),
            'create' => Pages\CreateSubDirection::route('/create'),
            'edit' => Pages\EditSubDirection::route('/{record}/edit'),
        ];
    }
}
