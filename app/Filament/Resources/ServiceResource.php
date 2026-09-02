<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use App\Models\Department;
use App\Models\SubDirection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    public static function getModelLabel(): string
    {
        return 'Service';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Services (Médical & Administratif)';
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
                Forms\Components\Section::make('Type de Service')
                    ->description('Choisissez si c\'est un service médical ou administratif')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de Service')
                            ->options([
                                'medical' => '🏥 Service Médical',
                                'administrative' => '🏢 Service Administratif',
                                'support' => '🛠️ Service Support',
                                'technical' => '⚙️ Service Technique',
                            ])
                            ->default('medical')
                            ->required()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Réinitialiser les rattachements lors du changement de type
                                if ($state === 'medical') {
                                    $set('sub_direction_id', null);
                                } else {
                                    $set('department_id', null);
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Rattachement Hiérarchique')
                    ->schema([
                        // Pour services médicaux
                        Forms\Components\Select::make('department_id')
                            ->label('Département Médical')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn(Forms\Get $get) => $get('type') === 'medical')
                            ->helperText('Rattachement au département médical')
                            ->columnSpanFull(),

                        // Pour services administratifs
                        Forms\Components\Select::make('sub_direction_id')
                            ->label('Sous-Direction')
                            ->relationship('subDirection', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn(Forms\Get $get) => in_array($get('type'), ['administrative', 'support', 'technical']))
                            ->helperText('Rattachement à la sous-direction administrative')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du Service')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Service de Cardiologie, Service Budget...')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->maxLength(50)
                            ->placeholder('CARD, BUDG...')
                            ->helperText('Code unique du service'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Responsabilité')
                    ->schema([
                        Forms\Components\Select::make('service_head_id')
                            ->label('Chef de Service')
                            ->relationship('serviceHead', 'matricule')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(
                                fn($record) =>
                                $record->full_name . ' (' . $record->matricule . ') - ' . ($record->position?->name ?? 'N/A')
                            )
                            ->helperText('Responsable du service')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Missions et activités du service...')
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
                            ->placeholder('Bâtiment, Étage, Bureau'),
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
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn($record) => $record->type_label)
                    ->colors([
                        'success' => 'medical',
                        'primary' => 'administrative',
                        'warning' => 'support',
                        'info' => 'technical',
                    ]),

                Tables\Columns\TextColumn::make('parent_name')
                    ->label('Rattachement')
                    ->getStateUsing(fn($record) => $record->parent_name)
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('serviceHead.full_name')
                    ->label('Chef de Service')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('employee_count')
                    ->label('Employés')
                    ->getStateUsing(fn($record) => $record->employee_count)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('sector_count')
                    ->label('Secteurs')
                    ->getStateUsing(fn($record) => $record->sector_count)
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'medical' => 'Médical',
                        'administrative' => 'Administratif',
                        'support' => 'Support',
                        'technical' => 'Technique',
                    ]),

                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Département Médical')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('sub_direction_id')
                    ->label('Sous-Direction')
                    ->relationship('subDirection', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),

                Tables\Filters\Filter::make('has_head')
                    ->label('Avec Chef de Service')
                    ->query(fn($query) => $query->whereNotNull('service_head_id')),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
