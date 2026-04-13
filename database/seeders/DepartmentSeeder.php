<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\MedicalDepartment;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Départements Administratifs
        $adminDepts = [
            ['name' => 'Direction Générale', 'code' => 'DG', 'type' => 'administrative', 'level' => 1],
            ['name' => 'Direction Administrative et Financière', 'code' => 'DAF', 'type' => 'administrative', 'level' => 2],
            ['name' => 'Direction des Ressources Humaines', 'code' => 'DRH', 'type' => 'administrative', 'level' => 2],
            ['name' => 'Direction des Approvisionnements', 'code' => 'DA', 'type' => 'administrative', 'level' => 2],
            ['name' => 'Direction de la Comptabilité', 'code' => 'DCOMPT', 'type' => 'administrative', 'level' => 2],
            ['name' => 'Service Informatique', 'code' => 'SI', 'type' => 'administrative', 'level' => 3],
            ['name' => 'Service Communication', 'code' => 'SCOM', 'type' => 'administrative', 'level' => 3],
        ];

        foreach ($adminDepts as $dept) {
            Department::create($dept);
        }

        // Départements Médicaux
        $medicalDepts = [
            ['name' => 'Département de Chirurgie', 'code' => 'CHIR'],
            ['name' => 'Département de Médecine', 'code' => 'MED'],
            ['name' => 'Département de Pédiatrie', 'code' => 'PED'],
            ['name' => 'Département de Gynécologie-Obstétrique', 'code' => 'GYNECO'],
            ['name' => 'Département des Urgences', 'code' => 'URG'],
            ['name' => 'Département d\'Imagerie Médicale', 'code' => 'IMG'],
            ['name' => 'Département de Laboratoire', 'code' => 'LAB'],
            ['name' => 'Département d\'Anesthésie-Réanimation', 'code' => 'ANESTH'],
        ];

        foreach ($medicalDepts as $dept) {
            MedicalDepartment::create($dept);
        }

        echo "✅ Départements créés!\n";
    }
}
