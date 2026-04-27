<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotpartDistributionResource\Pages;
use App\Models\QuotpartDistribution;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuotpartDistributionResource extends Resource
{
    protected static ?string $model = QuotpartDistribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = '💰 Quote-Parts';
    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Distribution Quote-Part';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Distributions Quote-Parts';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period.code')
                    ->label('Période')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable()
                    ->description(fn(QuotpartDistribution $record) => $record->employee?->matricule),

                Tables\Columns\TextColumn::make('total_points')
                    ->label('Points Totaux')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('gross_quotpart')
                    ->label('Quote-Part Brute')
                    ->money('XAF')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total_deductions')
                    ->label('Retenues')
                    ->money('XAF')
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('net_quotpart')
                    ->label('Net à Payer')
                    ->money('XAF')
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'calculated' => '📊 Calculée',
                        'approved' => '✅ Approuvée',
                        'paid' => '💰 Payée',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'calculated',
                        'success' => 'approved',
                        'primary' => 'paid',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period_id')
                    ->label('Période')
                    ->relationship('period', 'code'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'calculated' => 'Calculée',
                        'approved' => 'Approuvée',
                        'paid' => 'Payée',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('period_id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotpartDistributions::route('/'),
            'view' => Pages\ViewQuotpartDistribution::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Les distributions sont créées automatiquement par calcul
    }
}
