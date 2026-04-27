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
            DepartmentSeeder::class,
            PositionHierarchySeeder::class,
            OrganizationalStructureSeeder::class,
            LeaveTypeSeeder::class,
            SalaryGridSeeder::class,
            PayrollItemSeeder::class,
            SupplierSeeder::class,
            DocumentCategorySeeder::class,
            InitializeEmployeeEchelonsSeeder::class,
            NotificationTemplateSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
