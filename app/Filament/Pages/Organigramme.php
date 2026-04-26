<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Direction;

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

    public function getOrgData(): array
    {
        // Racine : Hôpital CHUY
        $data = [
            'name' => \App\Models\SystemSetting::get('hospital_name', 'CHUY'),
            'title' => 'HÔPITAL',
            'type' => 'root',
            'children' => [],
        ];

        // Charger les directions avec TOUTES leurs sous-structures
        $directions = Direction::with([
            'subDirections.services.serviceHead',    // Sous-directions administratives
            'departments.services.serviceHead',       // Départements médicaux (AJOUTÉ)
            'director'
        ])
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        foreach ($directions as $direction) {
            $directionNode = [
                'name' => $direction->name,
                'title' => $direction->code,
                'type' => 'direction',
                'director' => $direction->director?->full_name,
                'children' => [],
            ];

            // 1. AJOUTER LES SOUS-DIRECTIONS ADMINISTRATIVES
            foreach ($direction->subDirections()->orderBy('order')->get() as $subDir) {
                $subDirNode = [
                    'name' => $subDir->name,
                    'title' => $subDir->code,
                    'type' => 'sub_direction',
                    'hierarchical_level' => 'Sous-Direction',
                    'head' => $subDir->subDirector?->full_name,
                    'children' => [],
                ];

                // Services administratifs
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

            // 2. AJOUTER LES DÉPARTEMENTS MÉDICAUX (même niveau que sous-directions)
            foreach ($direction->departments()->orderBy('order')->get() as $dept) {
                $deptNode = [
                    'name' => $dept->name,
                    'title' => $dept->code,
                    'type' => 'department',
                    'hierarchical_level' => 'Département (Sous-Direction Médicale)',
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

                $directionNode['children'][] = $deptNode;
            }

            // Ajouter la direction à la racine
            $data['children'][] = $directionNode;
        }

        return $data;
    }

    protected function getViewData(): array
    {
        $orgData = $this->getOrgData();

        return [
            'orgData' => $orgData,
            'rawData' => $orgData,
        ];
    }
}
