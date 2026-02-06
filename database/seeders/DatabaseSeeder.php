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
            PositionSeeder::class,
            LeaveTypeSeeder::class,
            SalaryGridSeeder::class,
            PayrollItemSeeder::class,
            ProcurementTypeSeeder::class,
            SupplierSeeder::class,
            DocumentCategorySeeder::class,
            InitializeEmployeeEchelonsSeeder::class,
            NotificationTemplateSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
