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

        $departments = [
            [
                'name' => 'Département de Médecine Interne',
                'code' => 'DMI',
                'type' => 'medical',
                'description' => 'Prise en charge des pathologies médicales',
                'order' => 1,
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
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service de Chirurgie Générale', 'code' => 'CHIRGEN'],
                    ['name' => 'Service de Chirurgie Orthopédique', 'code' => 'ORTHO'],
                    ['name' => 'Service de Neurochirurgie', 'code' => 'NEUROCHIR'],
                ],
            ],
            [
                'name' => 'Département de Pédiatrie',
                'code' => 'DPED',
                'type' => 'medical',
                'description' => 'Soins aux enfants',
                'order' => 3,
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service de Néonatologie', 'code' => 'NEO'],
                    ['name' => 'Service de Pédiatrie Générale', 'code' => 'PEDGEN'],
                ],
            ],
            [
                'name' => 'Département de Gynécologie-Obstétrique',
                'code' => 'DGYN',
                'type' => 'medical',
                'description' => 'Santé de la femme et maternité',
                'order' => 4,
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service de Maternité', 'code' => 'MAT'],
                    ['name' => 'Service de Gynécologie', 'code' => 'GYN'],
                ],
            ],
            [
                'name' => 'Département des Urgences et Réanimation',
                'code' => 'DURG',
                'type' => 'medical',
                'description' => 'Urgences et soins intensifs',
                'order' => 5,
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service des Urgences', 'code' => 'URG'],
                    ['name' => 'Service de Réanimation', 'code' => 'REA'],
                ],
            ],
            [
                'name' => 'Département d\'Imagerie Médicale',
                'code' => 'DIMAG',
                'type' => 'diagnostic',
                'description' => 'Examens radiologiques et d\'imagerie',
                'order' => 6,
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service de Radiologie', 'code' => 'RADIO'],
                    ['name' => 'Service d\'Échographie', 'code' => 'ECHO'],
                    ['name' => 'Service de Scanner et IRM', 'code' => 'SCAN'],
                ],
            ],
            [
                'name' => 'Département de Biologie Médicale',
                'code' => 'DBIO',
                'type' => 'diagnostic',
                'description' => 'Analyses biologiques et laboratoires',
                'order' => 7,
                'hierarchical_level' => 'sub_direction',
                'services' => [
                    ['name' => 'Service d\'Hématologie', 'code' => 'HEMA'],
                    ['name' => 'Service de Biochimie', 'code' => 'BIOCH'],
                    ['name' => 'Service de Microbiologie', 'code' => 'MICRO'],
                ],
            ],
        ];

        foreach ($departments as $deptData) {
            $services = $deptData['services'] ?? [];
            unset($deptData['services']);

            // CHANGEMENT ICI : updateOrCreate
            $department = Department::updateOrCreate(
                ['code' => $deptData['code']],
                $deptData
            );

            $this->command->line("  ✓ {$department->name}");

            // Créer les services médicaux
            foreach ($services as $serviceData) {
                // CHANGEMENT ICI : updateOrCreate
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
