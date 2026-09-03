<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectorResource\Pages;
use App\Models\Sector;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class SectorResource extends Resource
{
    protected static ?string $model = Sector::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getModelLabel(): string
    {
        return 'Secteur';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Secteurs / Unités';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rattachement')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText('Service de rattachement')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du Secteur/Unité')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Secteur A, Bloc Opératoire, Unité X...')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->maxLength(50)
                            ->placeholder('SECT-A'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Type & Responsabilité')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de Secteur')
                            ->options([
                                'care_unit' => '🏥 Unité de Soins',
                                'operational' => '⚙️ Opérationnel',
                                'support' => '🛠️ Support',
                            ])
                            ->default('operational')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('sector_head_id')
                            ->label('Responsable')
                            ->relationship('sectorHead', 'matricule')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(
                                fn($record) =>
                                $record->full_name . ' (' . $record->matricule . ')'
                            )
                            ->helperText('Chef de secteur ou Major'),

                        Forms\Components\Select::make('head_title')
                            ->label('Titre du Responsable')
                            ->options([
                                'chef_secteur' => 'Chef de Secteur',
                                'major' => 'Major',
                                'responsable' => 'Responsable',
                            ])
                            ->default('chef_secteur')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Capacité (Médical)')
                    ->schema([
                        Forms\Components\TextInput::make('bed_capacity')
                            ->label('Nombre de Lits')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Pour les secteurs/unités de soins')
                            ->suffix('lits'),
                    ])
                    ->visible(fn(Forms\Get $get) => $get('type') === 'care_unit')
                    ->collapsible(),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Contact & Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('location')
                            ->label('Localisation')
                            ->maxLength(255)
                            ->placeholder('Aile Est, 3ème étage'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
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
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Secteur/Unité')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'care_unit' => '🏥 Soins',
                        'operational' => '⚙️ Opérationnel',
                        'support' => '🛠️ Support',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'care_unit',
                        'warning' => 'operational',
                        'info' => 'support',
                    ]),

                Tables\Columns\TextColumn::make('sectorHead.full_name')
                    ->label('Responsable')
                    ->searchable(),

                Tables\Columns\TextColumn::make('head_title')
                    ->label('Titre')
                    ->formatStateUsing(fn($record) => $record->head_title_label)
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bed_capacity')
                    ->label('Lits')
                    ->suffix(' lits')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'care_unit' => 'Unité de Soins',
                        'operational' => 'Opérationnel',
                        'support' => 'Support',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
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
            'index' => Pages\ListSectors::route('/'),
            'create' => Pages\CreateSector::route('/create'),
            'edit' => Pages\EditSector::route('/{record}/edit'),
        ];
    }
}
