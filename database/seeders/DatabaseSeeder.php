<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ContractTypeSeeder::class,
            OrganizationLevelSeeder::class,
            DepartmentSeeder::class,
            // ServiceSeeder::class, // On l'ajoutera après
        ]);
    }
}
