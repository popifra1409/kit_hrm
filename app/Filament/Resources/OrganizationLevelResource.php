<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationLevelResource\Pages;
use App\Filament\Resources\OrganizationLevelResource\RelationManagers;
use App\Models\OrganizationLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrganizationLevelResource extends Resource
{
    protected static ?string $model = OrganizationLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom du niveau')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex: Directeur Général, Chef de Service'),

                Forms\Components\TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('Ex: DG, CS'),

                Forms\Components\TextInput::make('hierarchy_level')
                    ->label('Niveau hiérarchique')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(20)
                    ->helperText('1 = Niveau le plus élevé (ex: PCA), 2 = DG, etc.'),

                Forms\Components\Select::make('branch')
                    ->label('Branche')
                    ->options([
                        'executive' => 'Direction Exécutive',
                        'medical' => 'Branche Médicale',
                        'administrative' => 'Branche Administrative',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hierarchy_level')
                    ->label('Niveau')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('branch')
                    ->label('Branche')
                    ->colors([
                        'danger' => 'executive',
                        'success' => 'medical',
                        'warning' => 'administrative',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'executive' => 'Direction Exécutive',
                        'medical' => 'Branche Médicale',
                        'administrative' => 'Branche Administrative',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('hierarchy_level', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch')
                    ->label('Branche')
                    ->options([
                        'executive' => 'Direction Exécutive',
                        'medical' => 'Branche Médicale',
                        'administrative' => 'Branche Administrative',
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
            'index' => Pages\ListOrganizationLevels::route('/'),
            'create' => Pages\CreateOrganizationLevel::route('/create'),
            'edit' => Pages\EditOrganizationLevel::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Niveaux Hiérarchiques';
    }
    public static function getModelLabel(): string
    {
        return 'Niveau Hiérarchique';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Niveaux Hiérarchiques';
    }
}
