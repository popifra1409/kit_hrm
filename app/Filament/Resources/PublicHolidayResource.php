<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PublicHolidayResource\Pages;
use App\Models\PublicHoliday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PublicHolidayResource extends Resource
{
    protected static ?string $model = PublicHoliday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function getModelLabel(): string
    {
        return 'Jour Férié';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jours Fériés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '⚙️ Paramétrage';
    }

    public static function getNavigationSort(): ?int
    {
        return 12;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                Forms\Components\TextInput::make('name')
                    ->label('Nom du jour férié')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex: Fête du Travail'),

                Forms\Components\Toggle::make('is_recurring_yearly')
                    ->label('Récurrent chaque année')
                    ->helperText('Activez pour les fêtes à date fixe (1er janvier, 1er mai...). Laissez désactivé pour les fêtes mobiles (Pâques, Aïd...) qui changent de date chaque année.'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_recurring_yearly')
                    ->label('Récurrent')
                    ->boolean(),
            ])
            ->defaultSort('date')
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
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
            'index' => Pages\ListPublicHolidays::route('/'),
            'create' => Pages\CreatePublicHoliday::route('/create'),
            'edit' => Pages\EditPublicHoliday::route('/{record}/edit'),
        ];
    }
}
