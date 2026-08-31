<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TradeBodyResource\Pages;
use App\Models\TradeBody;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class TradeBodyResource extends Resource
{
    protected static ?string $model = TradeBody::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du Corps de Métier')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ex: MED, TECH, ADM'),

                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'medical' => '🏥 Médical',
                                'technical' => '⚙️ Technique',
                                'administrative' => '📋 Administratif',
                                'support' => '🔧 Support',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->colors([
                        'success' => 'medical',
                        'warning' => 'technical',
                        'info' => 'administrative',
                        'gray' => 'support',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('qualifications_count')
                    ->label('Qualifications')
                    ->counts('qualifications')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employés')
                    ->counts('employees')
                    ->badge()
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'medical' => '🏥 Médical',
                        'technical' => '⚙️ Technique',
                        'administrative' => '📋 Administratif',
                        'support' => '🔧 Support',
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTradeBodies::route('/'),
            'create' => Pages\CreateTradeBody::route('/create'),
            'edit' => Pages\EditTradeBody::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Corps de Métier';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Corps de Métiers';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function getNavigationLabel(): string
    {
        return 'Corps de Métiers';
    }
}
