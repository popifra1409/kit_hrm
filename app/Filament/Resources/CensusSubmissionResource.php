<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CensusSubmissionResource\Pages;
use App\Models\CensusSubmission;
use App\Models\CensusCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CensusSubmissionResource extends Resource
{
    protected static ?string $model = CensusSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return 'Soumission de Recensement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Soumissions de Recensement';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable()
                    ->description(fn(CensusSubmission $record) => $record->employee?->matricule),

                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campagne')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'submitted' => '⏳ En attente',
                        'validated' => '✅ Validé',
                        'rejected' => '❌ Rejeté',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'submitted',
                        'success' => 'validated',
                        'danger' => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('Traité par')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('census_campaign_id')
                    ->label('Campagne')
                    ->options(fn() => CensusCampaign::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'submitted' => 'En attente',
                        'validated' => 'Validé',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Examiner')
                    ->icon('heroicon-o-magnifying-glass')
                    ->url(fn(CensusSubmission $record) => static::getUrl('review', ['record' => $record])),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCensusSubmissions::route('/'),
            'review' => Pages\ReviewCensusSubmission::route('/{record}/review'),
        ];
    }
}
