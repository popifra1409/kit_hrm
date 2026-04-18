<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Direction;
use App\Models\SubDirection;
use App\Models\Department;
use App\Models\Service;
use App\Models\Sector;

class OrganizationalStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏗️ Création de la structure organisationnelle...');

        // 1. DIRECTIONS ADMINISTRATIVES
        $this->createDirections();

        // 2. DÉPARTEMENTS MÉDICAUX (niveau Sous-Direction)
        $this->createMedicalDepartments();

        $this->command->info('✅ Structure organisationnelle créée avec succès !');
    }

    protected function createDirections()
    {
        $this->command->line('📌 Création des Directions...');

        $directions = [
            [
                'name' => 'Direction Générale',
                'code' => 'DG',
                'acronym' => 'DG',
                'type' => 'administrative',
                'description' => 'Direction Générale de l\'hôpital',
                'order' => 1,
            ],
            [
                'name' => 'Direction des Ressources Humaines',
                'code' => 'DRH',
                'acronym' => 'DRH',
                'type' => 'administrative',
                'description' => 'Gestion du personnel et développement RH',
                'order' => 2,
            ],
            [
                'name' => 'Direction Administrative et Financière',
                'code' => 'DAF',
                'acronym' => 'DAF',
                'type' => 'administrative',
                'description' => 'Gestion administrative, financière et comptable',
                'order' => 3,
            ],
            [
                'name' => 'Direction des Affaires Médicales',
                'code' => 'DAM',
                'acronym' => 'DAM',
                'type' => 'administrative',
                'description' => 'Coordination et supervision des activités médicales',
                'order' => 4,
            ],
            [
                'name' => 'Direction des Soins Infirmiers',
                'code' => 'DSI',
                'acronym' => 'DSI',
                'type' => 'administrative',
                'description' => 'Coordination des soins infirmiers et paramédicaux',
                'order' => 5,
            ],
        ];

        foreach ($directions as $directionData) {
            // CHANGEMENT ICI : updateOrCreate au lieu de create
            $direction = Direction::updateOrCreate(
                ['code' => $directionData['code']],
                $directionData
            );

            $this->command->line("  ✓ {$direction->name}");

            // Créer sous-directions pour chaque direction
            $this->createSubDirections($direction);
        }
    }

    protected function createSubDirections(Direction $direction)
    {
        $subDirections = match ($direction->code) {
            'DRH' => [
                ['name' => 'Sous-Direction de la Gestion du Personnel', 'code' => 'SDGP', 'order' => 1],
                ['name' => 'Sous-Direction du Développement RH', 'code' => 'SDDRH', 'order' => 2],
            ],
            'DAF' => [
                ['name' => 'Sous-Direction des Finances', 'code' => 'SDF', 'order' => 1],
                ['name' => 'Sous-Direction de la Comptabilité', 'code' => 'SDC', 'order' => 2],
                ['name' => 'Sous-Direction des Marchés Publics', 'code' => 'SDMP', 'order' => 3],
            ],
            'DAM' => [
                ['name' => 'Sous-Direction de la Qualité des Soins', 'code' => 'SDQS', 'order' => 1],
                ['name' => 'Sous-Direction de la Recherche Médicale', 'code' => 'SDRM', 'order' => 2],
            ],
            default => [],
        };

        foreach ($subDirections as $subDirData) {
            // CHANGEMENT ICI : updateOrCreate
            $subDir = SubDirection::updateOrCreate(
                [
                    'direction_id' => $direction->id,
                    'code' => $subDirData['code']
                ],
                [
                    'name' => $subDirData['name'],
                    'order' => $subDirData['order'],
                    'is_active' => true,
                ]
            );

            $this->command->line("    → {$subDir->name}");

            // Créer services pour certaines sous-directions
            $this->createAdministrativeServices($subDir);
        }
    }

    protected function createAdministrativeServices(SubDirection $subDirection)
    {
        $services = match ($subDirection->code) {
            'SDGP' => [
                ['name' => 'Service de la Paie', 'code' => 'PAIE'],
                ['name' => 'Service des Congés et Absences', 'code' => 'CONGES'],
                ['name' => 'Service de la Formation', 'code' => 'FORM'],
            ],
            'SDF' => [
                ['name' => 'Service Budget', 'code' => 'BUDG'],
                ['name' => 'Service Trésorerie', 'code' => 'TRES'],
            ],
            'SDC' => [
                ['name' => 'Service Comptabilité Générale', 'code' => 'COMPTG'],
                ['name' => 'Service Comptabilité Analytique', 'code' => 'COMPTA'],
            ],
            default => [],
        };

        foreach ($services as $serviceData) {
            // CHANGEMENT ICI : updateOrCreate
            $service = Service::updateOrCreate(
                [
                    'sub_direction_id' => $subDirection->id,
                    'code' => $serviceData['code']
                ],
                [
                    'name' => $serviceData['name'],
                    'type' => 'administrative',
                    'is_active' => true,
                ]
            );

            $this->command->line("      • {$service->name}");
        }
    }

    protected function createMedicalDepartments()
    {
        $this->command->line('📌 Création des Départements Médicaux...');

        // Récupérer la Direction des Affaires Médicales (DAM)
        $dam = Direction::where('code', 'DAM')->first();

        // Si DAM n'existe pas, la créer
        if (!$dam) {
            $dam = Direction::updateOrCreate(
                ['code' => 'DAM'],
                [
                    'name' => 'Direction des Affaires Médicales',
                    'acronym' => 'DAM',
                    'type' => 'administrative',
                    'description' => 'Coordination et supervision des activités médicales',
                    'order' => 4,
                    'is_active' => true,
                ]
            );
        }

        $departments = [
            [
                'name' => 'Département de Médecine Interne',
                'code' => 'DMI',
                'type' => 'medical',
                'description' => 'Prise en charge des pathologies médicales',
                'order' => 1,
                'direction_id' => $dam->id,  // AJOUT
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service de Cardiologie', 'code' => 'CARD'],
                    ['name' => 'Service de Pneumologie', 'code' => 'PNEUMO'],
                    ['name' => 'Service de Néphrologie', 'code' => 'NEPHRO'],
                ],
            ],
            [
                'name' => 'Département de Chirurgie',
                'code' => 'DCHIR',
                'type' => 'surgical',
                'description' => 'Interventions chirurgicales',
                'order' => 2,
                'direction_id' => $dam->id,  // AJOUT
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service de Chirurgie Générale', 'code' => 'CHIRGEN'],
                    ['name' => 'Service de Chirurgie Orthopédique', 'code' => 'ORTHO'],
                    ['name' => 'Service de Neurochirurgie', 'code' => 'NEUROCHIR'],
                ],
            ],
            // ... ajoutez direction_id: $dam->id à tous les départements
        ];

        foreach ($departments as $deptData) {
            $services = $deptData['services'] ?? [];
            unset($deptData['services']);

            $department = Department::updateOrCreate(
                ['code' => $deptData['code']],
                $deptData
            );

            $this->command->line("  ✓ {$department->name} (rattaché à {$dam->name})");

            // Créer les services médicaux
            foreach ($services as $serviceData) {
                $service = Service::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'code' => $serviceData['code']
                    ],
                    [
                        'name' => $serviceData['name'],
                        'type' => 'medical',
                        'is_active' => true,
                    ]
                );

                $this->command->line("    → {$service->name}");
            }
        }
    }
}
