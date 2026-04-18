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
        $soignantCount = Employee::where('personnel_type', 'soignant')->count();
        $paramedicalCount = Employee::where('personnel_type', 'paramedical')->count();
        $nonSoignantCount = Employee::where('personnel_type', 'non_soignant')->count();
        $autresCount = Employee::where('personnel_type', 'autres')->count();

        return [
            Stat::make('Personnel Soignant', $soignantCount)
                ->description('Médecins, Chirurgiens')
                ->descriptionIcon('heroicon-o-user-circle')
                ->color('success'),

            Stat::make('Personnel Paramédical', $paramedicalCount)
                ->description('Infirmiers, Aides-soignants')
                ->descriptionIcon('heroicon-o-heart')
                ->color('info'),

            Stat::make('Personnel Non-Soignant', $nonSoignantCount)
                ->description('Administratif, Technique, Support')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('warning'),

            Stat::make('Autres', $autresCount)
                ->description('Autres catégories')
                ->descriptionIcon('heroicon-o-ellipsis-horizontal')
                ->color('gray'),
        ];
    }

    protected static ?int $sort = 1;
}
