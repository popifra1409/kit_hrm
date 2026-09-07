<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Dependent;
use App\Models\Employee;
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

        // ✅ CORRIGÉ : arrondi à 1 décimale au lieu du nombre brut (ex: 74.8333333333)
        $avgDependentCoverage = round((float) Dependent::where('is_active', true)->avg('coverage_rate'), 1);

        // Taux effectif des employés eux-mêmes (75% actifs, 50% retraités par défaut,
        // calculé via Employee::effective_coverage_rate — rien de stocké à additionner).
        $activeEmployees = Employee::where('is_active', true)->get();
        $retiredEmployees = Employee::where('is_active', false)
            ->whereNotNull('retirement_date')
            ->where('retirement_date', '<=', now())
            ->get();

        $allRelevantEmployees = $activeEmployees->merge($retiredEmployees);

        $avgEmployeeCoverage = $allRelevantEmployees->isNotEmpty()
            ? round($allRelevantEmployees->avg(fn($e) => $e->effective_coverage_rate), 1)
            : 0;

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

            Stat::make('Taux Couverture Moyen (Ayants Droit)', $avgDependentCoverage . '%')
                ->description('Moyenne de prise en charge')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make('Taux Couverture Moyen (Employés)', $avgEmployeeCoverage . '%')
                ->description($retiredEmployees->count() . ' retraité(s) à 50% inclus')
                ->descriptionIcon('heroicon-o-identification')
                ->color('primary'),
        ];
    }
}
