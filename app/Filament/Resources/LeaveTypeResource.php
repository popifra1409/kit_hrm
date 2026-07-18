<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveTypeResource\Pages;
use App\Filament\Resources\LeaveTypeResource\RelationManagers;
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du type de congé')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ex: CA, CM, CMAT'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\TextInput::make('default_days')
                            ->label('Nombre de jours par défaut')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('jours'),

                        Forms\Components\TextInput::make('max_days_per_year')
                            ->label('Maximum par an')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->suffix('jours'),

                        Forms\Components\Toggle::make('requires_document')
                            ->label('Nécessite un justificatif')
                            ->default(false)
                            ->helperText('Certificat médical, acte de naissance, etc.'),

                        Forms\Components\Toggle::make('is_paid')
                            ->label('Congé payé')
                            ->default(true),

                        Forms\Components\Toggle::make('deductible_from_annual')
                            ->label('Déductible du congé annuel')
                            ->default(false)
                            ->helperText('Si oui, sera déduit des 30 jours de congé annuel'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])
                    ->columns(3),
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
                    ->badge(),

                Tables\Columns\TextColumn::make('default_days')
                    ->label('Jours par défaut')
                    ->suffix(' jours')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_days_per_year')
                    ->label('Maximum/an')
                    ->suffix(' jours')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('requires_document')
                    ->label('Justificatif')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Payé')
                    ->boolean(),

                Tables\Columns\IconColumn::make('deductible_from_annual')
                    ->label('Déduit CA')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Congé payé'),
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
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Type de Congé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Types de Congés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏖️ Congés & Absences';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }
}
