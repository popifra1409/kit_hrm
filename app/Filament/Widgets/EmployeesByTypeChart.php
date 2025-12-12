<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Employee;

class EmployeesByTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Répartition Personnel Soignant / Non-Soignant';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $soignants = Employee::where('is_active', true)
            ->where('personnel_type', 'soignant')
            ->count();

        $nonSoignants = Employee::where('is_active', true)
            ->where('personnel_type', 'non_soignant')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Personnel',
                    'data' => [$soignants, $nonSoignants],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)', // Bleu pour soignant
                        'rgb(234, 179, 8)',  // Jaune pour non-soignant
                    ],
                ],
            ],
            'labels' => ['Personnel Soignant', 'Personnel Non-Soignant'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
