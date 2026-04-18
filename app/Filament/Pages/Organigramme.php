<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Direction;
use App\Models\SubDirection;
use App\Models\Department;
use App\Models\Service;
use App\Models\Sector;
use App\Models\Employee;

class Organigramme extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.organigramme';

    protected static ?string $navigationGroup = '🏢 Structure Organisationnelle';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return 'Organigramme';
    }

    /**
     * Préparer les données pour l'organigramme D3.js
     */
    public function getOrgData(): array
    {
        // Structure hiérarchique complète
        $data = [
            'name' => 'Direction Générale',
            'title' => 'DG',
            'children' => [],
        ];

        // 1. Charger les Directions
        $directions = Direction::with(['subDirections.services', 'director'])
            ->where('is_active', true)
            ->orderBy('order')  // CHANGÉ ICI
            ->get();

        foreach ($directions as $direction) {
            $directionNode = [
                'name' => $direction->name,
                'title' => $direction->code,
                'type' => 'direction',
                'director' => $direction->director?->full_name,
                'children' => [],
            ];

            // 2. Sous-Directions
            foreach ($direction->subDirections()->orderBy('order')->get() as $subDir) {
                $subDirNode = [
                    'name' => $subDir->name,
                    'title' => $subDir->code,
                    'type' => 'sub_direction',
                    'head' => $subDir->subDirector?->full_name,
                    'children' => [],
                ];

                // 3. Services administratifs
                foreach ($subDir->services()->orderBy('order')->get() as $service) {
                    $subDirNode['children'][] = [
                        'name' => $service->name,
                        'title' => $service->code,
                        'type' => 'service',
                        'head' => $service->serviceHead?->full_name,
                    ];
                }

                $directionNode['children'][] = $subDirNode;
            }

            $data['children'][] = $directionNode;
        }

        // 4. Départements Médicaux
        $departments = Department::with(['services', 'departmentHead'])
            ->where('is_active', true)
            ->orderBy('order')  // CHANGÉ ICI
            ->get();

        foreach ($departments as $dept) {
            $deptNode = [
                'name' => $dept->name,
                'title' => $dept->code,
                'type' => 'department',
                'head' => $dept->departmentHead?->full_name,
                'children' => [],
            ];

            // Services médicaux
            foreach ($dept->services()->orderBy('order')->get() as $service) {
                $deptNode['children'][] = [
                    'name' => $service->name,
                    'title' => $service->code,
                    'type' => 'service_medical',
                    'head' => $service->serviceHead?->full_name,
                ];
            }

            $data['children'][] = $deptNode;
        }

        return $data;
    }

    /**
     * Données pour le composant Livewire
     */
    protected function getViewData(): array
    {
        return [
            'orgData' => json_encode($this->getOrgData()),
        ];
    }
}
