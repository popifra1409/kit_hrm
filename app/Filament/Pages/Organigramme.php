<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Employee;
use App\Models\EmployeeHierarchy;
use App\Models\OrganizationLevel;
use App\Models\Department;
use App\Models\Service;

class Organigramme extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Organigramme';
    protected static ?string $title = 'Organigramme de l\'Hôpital';
    protected static ?string $navigationGroup = '🏢 Structure Organisationnelle';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.organigramme';

    public function getOrgData()
    {
        // Récupérer les hiérarchies actuelles
        $hierarchies = EmployeeHierarchy::where('is_current', true)
            ->with(['employee', 'organizationLevel', 'superior'])
            ->orderBy('organization_level_id')
            ->get();

        // Construire l'arbre hiérarchique
        $orgTree = [
            'name' => 'Hôpital Général de Yaoundé',
            'title' => 'Établissement',
            'children' => []
        ];

        // Niveau 1 : PCA
        $pca = $hierarchies->where('organizationLevel.hierarchy_level', 1)->first();
        if ($pca && $pca->employee) {
            $pcaNode = [
                'name' => $pca->employee->full_name,
                'title' => $pca->organizationLevel->name,
                'children' => []
            ];

            // Niveau 2 : DG
            $dg = $hierarchies->where('organizationLevel.hierarchy_level', 2)
                ->where('superior_id', $pca->employee_id)
                ->first();

            if ($dg && $dg->employee) {
                $dgNode = [
                    'name' => $dg->employee->full_name,
                    'title' => $dg->organizationLevel->name,
                    'children' => []
                ];

                // Niveau 3 : DGA
                $dga = $hierarchies->where('organizationLevel.hierarchy_level', 3)->first();
                if ($dga && $dga->employee) {
                    $dgNode['children'][] = [
                        'name' => $dga->employee->full_name,
                        'title' => $dga->organizationLevel->name,
                    ];
                }

                // Directeurs (niveau 4)
                $directors = $hierarchies->where('organizationLevel.hierarchy_level', 4)->all();
                foreach ($directors as $director) {
                    if ($director->employee) {
                        $dgNode['children'][] = [
                            'name' => $director->employee->full_name,
                            'title' => $director->organizationLevel->name .
                                ($director->department ? ' - ' . $director->department->name : '') .
                                ($director->medicalDepartment ? ' - ' . $director->medicalDepartment->name : ''),
                        ];
                    }
                }

                $pcaNode['children'][] = $dgNode;
            }

            $orgTree['children'][] = $pcaNode;
        }

        return json_encode($orgTree);
    }
}
