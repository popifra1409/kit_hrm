<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Service;
use App\Support\OrganizationalAssignment;

class StatsOverview extends BaseWidget
{
    /**
     * Une tuile par catégorie, avec l'aide qui va bien : la répartition n'est plus
     * « soignant / non soignant », et le nombre de cases suit la nomenclature
     * toute seule quand on y ajoute une catégorie.
     */
    protected function getStats(): array
    {
        $counts = Employee::whereIn('personnel_type', array_keys(OrganizationalAssignment::personnelTypeOptions()))
            ->selectRaw('personnel_type, count(*) aggregate')
            ->groupBy('personnel_type')
            ->pluck('aggregate', 'personnel_type');

        $stats = [];

        foreach (OrganizationalAssignment::PERSONNEL_TYPES as $type => $label) {
            $stats[] = Stat::make($label, (int) $counts->get($type, 0))
                ->description(OrganizationalAssignment::PERSONNEL_TYPE_GUIDES[$type])
                ->color(OrganizationalAssignment::personnelTypeColor($type));
        }

        $toReclassify = Employee::whereIn('personnel_type', array_keys(OrganizationalAssignment::LEGACY_PERSONNEL_TYPES))->count();

        $stats[] = Stat::make('À reclasser', $toReclassify)
            ->description('Fiches encore sur l\'ancienne nomenclature')
            ->color($toReclassify > 0 ? 'danger' : 'gray');

        return $stats;
    }

    protected static ?int $sort = 1;
}
