<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Service;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        $soignants = Employee::where('is_active', true)->where('personnel_type', 'soignant')->count();
        $nonSoignants = Employee::where('is_active', true)->where('personnel_type', 'non_soignant')->count();

        // Employés en congé
        $onLeave = Employee::where('status', 'on_leave')->count();

        // Retraites dans les 12 prochains mois
        $upcomingRetirements = Employee::where('is_active', true)
            ->whereNotNull('retirement_date')
            ->whereBetween('retirement_date', [now(), now()->addYear()])
            ->count();

        return [
            Stat::make('Total Employés', $totalEmployees)
                ->description('Employés actifs')
                ->descriptionIcon('heroicon-o-users')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Personnel Soignant', $soignants)
                ->description($nonSoignants . ' non-soignants')
                ->descriptionIcon('heroicon-o-heart')
                ->color('info'),

            Stat::make('Départements', Department::where('is_active', true)->count())
                ->description(Service::where('is_active', true)->count() . ' services')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('warning'),

            Stat::make('En Congé', $onLeave)
                ->description('Employés actuellement en congé')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('Retraites Proches', $upcomingRetirements)
                ->description('Dans les 12 prochains mois')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger'),
        ];
    }

    protected static ?int $sort = 1;
}
