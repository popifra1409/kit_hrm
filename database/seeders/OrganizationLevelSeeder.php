<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrganizationLevel;

class OrganizationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            // Niveau Exécutif
            ['name' => 'Président du Conseil d\'Administration', 'code' => 'PCA', 'hierarchy_level' => 1, 'branch' => 'executive'],
            ['name' => 'Directeur Général', 'code' => 'DG', 'hierarchy_level' => 2, 'branch' => 'executive'],
            ['name' => 'Directeur Général Adjoint', 'code' => 'DGA', 'hierarchy_level' => 3, 'branch' => 'executive'],

            // Niveau Direction
            ['name' => 'Directeur', 'code' => 'DIR', 'hierarchy_level' => 4, 'branch' => 'administrative'],
            ['name' => 'Sous-Directeur', 'code' => 'SDIR', 'hierarchy_level' => 5, 'branch' => 'administrative'],

            // Niveau Médical
            ['name' => 'Chef de Département Médical', 'code' => 'CDM', 'hierarchy_level' => 4, 'branch' => 'medical'],
            ['name' => 'Chef de Service Médical', 'code' => 'CSM', 'hierarchy_level' => 5, 'branch' => 'medical'],
            ['name' => 'Major', 'code' => 'MAJ', 'hierarchy_level' => 6, 'branch' => 'medical'],

            // Niveau Service Administratif
            ['name' => 'Chef de Service Administratif', 'code' => 'CSA', 'hierarchy_level' => 6, 'branch' => 'administrative'],
        ];

        foreach ($levels as $level) {
            OrganizationLevel::create($level);
        }

        echo "✅ Niveaux d'organisation créés!\n";
    }
}
