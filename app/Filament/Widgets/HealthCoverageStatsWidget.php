<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Dependent;
use App\Models\EmployeeCard;

class HealthCoverageStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $totalDependents = Dependent::where('is_active', true)->count();
        $totalCards = EmployeeCard::where('card_type', 'health_coverage')
            ->where('is_active', true)
            ->count();

        $dependentsByType = Dependent::where('is_active', true)
            ->selectRaw('relationship, COUNT(*) as count')
            ->groupBy('relationship')
            ->pluck('count', 'relationship');

        return [
            Stat::make('Ayants Droit Actifs', $totalDependents)
                ->description('Total bénéficiaires')
                ->descriptionIcon('heroicon-o-users')
                ->color('success')
                ->chart([
                    $dependentsByType->get('spouse', 0),
                    $dependentsByType->get('child', 0),
                    $dependentsByType->get('father', 0),
                    $dependentsByType->get('mother', 0),
                ]),

            Stat::make('Cartes Santé Actives', $totalCards)
                ->description('Cartes de prise en charge')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('info'),

            Stat::make('Taux Couverture Moyen', Dependent::where('is_active', true)->avg('coverage_rate') . '%')
                ->description('Moyenne de prise en charge')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),
        ];
    }
}
