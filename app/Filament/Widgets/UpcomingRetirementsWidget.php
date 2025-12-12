<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Employee;
use Carbon\Carbon;

class UpcomingRetirementsWidget extends BaseWidget
{
    // SUPPRIMEZ cette ligne : protected static ?string $heading = 'Alertes Retraites';
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        // Retraites dans les 3 prochains mois
        $in3Months = Employee::where('is_active', true)
            ->whereNotNull('retirement_date')
            ->whereBetween('retirement_date', [now(), now()->addMonths(3)])
            ->count();

        // Retraites dans 3-6 mois
        $in6Months = Employee::where('is_active', true)
            ->whereNotNull('retirement_date')
            ->whereBetween('retirement_date', [now()->addMonths(3), now()->addMonths(6)])
            ->count();

        // Retraites dans 6-12 mois
        $in12Months = Employee::where('is_active', true)
            ->whereNotNull('retirement_date')
            ->whereBetween('retirement_date', [now()->addMonths(6), now()->addYear()])
            ->count();

        return [
            Stat::make('< 3 mois', $in3Months)
                ->description('Retraites très proches')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('3-6 mois', $in6Months)
                ->description('À anticiper')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('6-12 mois', $in12Months)
                ->description('À planifier')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
        ];
    }
}
