<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Support\OrganizationalAssignment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * Pyramide des effectifs : une barre par catégorie de personnel, les hommes à
 * gauche, les femmes à droite. La catégorie tient lieu d'âge — c'est la
 * répartition que le recensement produit, et la dichotomie soignant / non
 * soignant n'a plus rien à départager.
 */
class EmployeesByTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Pyramide des effectifs par catégorie de personnel';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = Employee::query()
            ->where('is_active', true)
            ->selectRaw('personnel_type, gender, count(*) aggregate')
            ->groupBy('personnel_type', 'gender')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $side = match ($row->gender) {
                'M' => 'men',
                'F' => 'women',
                default => 'unknown',
            };

            $counts[$row->personnel_type][$side] = ($counts[$row->personnel_type][$side] ?? 0) + (int) $row->aggregate;
        }

        $labels = [];
        $men = [];
        $women = [];
        $unknown = [];

        foreach (OrganizationalAssignment::personnelTypeOptions() as $type => $label) {
            $side = $counts[$type] ?? [];

            // Une catégorie en vigueur à zéro reste affichée : sans sa barre vide,
            // rien ne distingue « personne dans cette catégorie » de « personne n'a
            // encore été reclassée là ». Une héritée vide n'apporte rien.
            if ($side === [] && ! isset(OrganizationalAssignment::PERSONNEL_TYPES[$type])) {
                continue;
            }

            $labels[] = $label;
            $men[] = -1 * ($side['men'] ?? 0);
            $women[] = $side['women'] ?? 0;
            $unknown[] = $side['unknown'] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Hommes',
                    'data' => $men,
                    'backgroundColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Femmes',
                    'data' => $women,
                    'backgroundColor' => 'rgb(236, 72, 153)',
                ],
                [
                    'label' => 'Sexe non renseigné',
                    'data' => $unknown,
                    'backgroundColor' => 'rgb(156, 163, 175)',
                ],
            ],
        ];
    }

    /**
     * Barres horizontales empilées. Les valeurs hommes sont saisies en négatif
     * pour pousser la barre vers la gauche ; Chart.js empile les signs séparément,
     * d'où l'axe des catégories qui reste au milieu. Les rappels affichent la
     * valeur absolue, sinon l'effectif se lit « -12 ».
     */
    protected function getOptions(): RawJs
    {
        return new RawJs(<<<'JS'
            {
                indexAxis: 'y',
                scales: {
                    x: {
                        stacked: true,
                        ticks: {
                            callback: (value) => Math.abs(value),
                        },
                    },
                    y: {
                        stacked: true,
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label} : ${Math.abs(context.parsed.x)}`,
                        },
                    },
                },
            }
        JS);
    }
}
