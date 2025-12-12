<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions pour chaque module
        $permissions = [
            // Permissions Utilisateurs
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Permissions Employés
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',

            // Permissions Congés
            'view_leaves',
            'create_leaves',
            'edit_leaves',
            'delete_leaves',
            'approve_leaves',

            // Permissions Paie
            'view_payrolls',
            'create_payrolls',
            'edit_payrolls',
            'delete_payrolls',
            'calculate_payrolls',

            // Permissions Marchés
            'view_tenders',
            'create_tenders',
            'edit_tenders',
            'delete_tenders',
            'approve_tenders',

            // Permissions Documents
            'view_documents',
            'create_documents',
            'edit_documents',
            'delete_documents',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions

        // Super Admin - Toutes les permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin RH - Gestion du personnel et congés
        $adminRH = Role::create(['name' => 'Admin RH']);
        $adminRH->givePermissionTo([
            'view_users',
            'view_employees',
            'create_employees',
            'edit_employees',
            'view_leaves',
            'create_leaves',
            'edit_leaves',
            'approve_leaves',
            'view_documents'
        ]);

        // Gestionnaire Paie - Gestion de la paie
        $gestionnairePaie = Role::create(['name' => 'Gestionnaire Paie']);
        $gestionnairePaie->givePermissionTo([
            'view_employees',
            'view_payrolls',
            'create_payrolls',
            'edit_payrolls',
            'calculate_payrolls',
            'view_documents'
        ]);

        // Responsable Marchés - Gestion des marchés publics
        $responsableMarches = Role::create(['name' => 'Responsable Marchés']);
        $responsableMarches->givePermissionTo([
            'view_tenders',
            'create_tenders',
            'edit_tenders',
            'approve_tenders',
            'view_documents'
        ]);

        // Personnel - Consultation uniquement
        $personnel = Role::create(['name' => 'Personnel']);
        $personnel->givePermissionTo([
            'view_employees',
            'create_leaves',
            'view_leaves',
            'view_documents'
        ]);

        // Auditeur - Lecture seule
        $auditeur = Role::create(['name' => 'Auditeur']);
        $auditeur->givePermissionTo([
            'view_users',
            'view_employees',
            'view_leaves',
            'view_payrolls',
            'view_tenders',
            'view_documents'
        ]);

        echo "✅ Rôles et permissions créés avec succès!\n";
    }
}
