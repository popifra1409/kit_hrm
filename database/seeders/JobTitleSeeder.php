<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobTitle;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $jobTitles = [
            [
                'name' => 'Président du Conseil d\'Administration',
                'code' => 'PRES',
                'description' => 'Président du conseil d\'administration',
                'level' => 'president',
                'hierarchy_level' => 0,
                'is_managerial' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Directeur Général',
                'code' => 'DG',
                'description' => 'Directeur général de l\'établissement',
                'level' => 'director_general',
                'hierarchy_level' => 1,
                'is_managerial' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Directeur Général Adjoint',
                'code' => 'DGA',
                'description' => 'Directeur général adjoint',
                'level' => 'director_general_adjoint',
                'hierarchy_level' => 2,
                'is_managerial' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Directeur',
                'code' => 'DIR',
                'description' => 'Directeur de direction',
                'level' => 'director',
                'hierarchy_level' => 3,
                'is_managerial' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Chef de Département/Sous-Direction',
                'code' => 'CHEF-DEPT',
                'description' => 'Chef de département médical ou sous-direction administrative',
                'level' => 'chief_department',
                'hierarchy_level' => 4,
                'is_managerial' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Chef de Service',
                'code' => 'CHEF-SERV',
                'description' => 'Chef de service médical ou administratif',
                'level' => 'chief_service',
                'hierarchy_level' => 5,
                'is_managerial' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Major',
                'code' => 'MAJOR',
                'description' => 'Major de service',
                'level' => 'major',
                'hierarchy_level' => 6,
                'is_managerial' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Chef d\'Unité/Secteur',
                'code' => 'CHEF-UNIT',
                'description' => 'Chef d\'unité ou de secteur',
                'level' => 'chief_unit',
                'hierarchy_level' => 7,
                'is_managerial' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Employé',
                'code' => 'EMP',
                'description' => 'Employé standard',
                'level' => 'employee',
                'hierarchy_level' => 10,
                'is_managerial' => false,
                'is_active' => true,
            ],
        ];

        foreach ($jobTitles as $jobTitle) {
            JobTitle::firstOrCreate(
                ['code' => $jobTitle['code']],
                $jobTitle
            );
        }

        echo "✅ " . count($jobTitles) . " titres de poste créés/mis à jour\n";
    }
}
