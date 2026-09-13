<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CensusCampaignResource\Pages;
use App\Models\CensusCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CensusCampaignResource extends Resource
{
    protected static ?string $model = CensusCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function getModelLabel(): string
    {
        return 'Campagne de Recensement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Recensement des Employés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom de la campagne')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex: Recensement Annuel 2026'),

                Forms\Components\Textarea::make('description')
                    ->label('Description / Instructions')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => '📝 Brouillon (pas encore visible des employés)',
                        'open' => '🟢 Ouverte (les employés peuvent soumettre)',
                        'closed' => '🔴 Fermée (retour au mode normal)',
                    ])
                    ->default('draft')
                    ->required()
                    ->native(false)
                    ->helperText('Une seule campagne devrait être "Ouverte" à la fois.'),

                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Début (informatif)')
                    ->native(false),

                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Fin (informatif)')
                    ->native(false),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'draft' => 'Brouillon',
                        'open' => 'Ouverte',
                        'closed' => 'Fermée',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'open',
                        'danger' => 'closed',
                    ]),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Soumissions')
                    ->counts('submissions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),

                Tables\Actions\Action::make('view_submissions')
                    ->label('Voir les soumissions')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->url(fn(CensusCampaign $record) => \App\Filament\Resources\CensusSubmissionResource::getUrl('index', [
                        'tableFilters[census_campaign_id][value]' => $record->id,
                    ])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCensusCampaigns::route('/'),
            'create' => Pages\CreateCensusCampaign::route('/create'),
            'edit' => Pages\EditCensusCampaign::route('/{record}/edit'),
        ];
    }
}
