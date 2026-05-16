<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================================
        // CRÉATION DES PERMISSIONS PAR MODULE
        // ============================================

        $permissions = [
            // === SUPER ADMIN (permissions critiques) ===
            ['name' => 'manage_super_admin', 'module' => 'system', 'description' => 'Gérer le super administrateur'],
            ['name' => 'manage_admins', 'module' => 'system', 'description' => 'Gérer les administrateurs'],
            ['name' => 'manage_all_roles', 'module' => 'system', 'description' => 'Gérer tous les rôles'],
            ['name' => 'manage_all_permissions', 'module' => 'system', 'description' => 'Gérer toutes les permissions'],
            ['name' => 'access_system_logs', 'module' => 'system', 'description' => 'Accéder aux logs système'],
            ['name' => 'manage_database', 'module' => 'system', 'description' => 'Gérer la base de données'],
            ['name' => 'manage_backups', 'module' => 'system', 'description' => 'Gérer les sauvegardes'],

            // === UTILISATEURS ===
            ['name' => 'view_users', 'module' => 'users', 'description' => 'Voir les utilisateurs'],
            ['name' => 'create_users', 'module' => 'users', 'description' => 'Créer des utilisateurs'],
            ['name' => 'edit_users', 'module' => 'users', 'description' => 'Modifier les utilisateurs'],
            ['name' => 'delete_users', 'module' => 'users', 'description' => 'Supprimer les utilisateurs'],

            // === EMPLOYÉS ===
            ['name' => 'view_employees', 'module' => 'employees', 'description' => 'Voir les employés'],
            ['name' => 'create_employees', 'module' => 'employees', 'description' => 'Créer des employés'],
            ['name' => 'edit_employees', 'module' => 'employees', 'description' => 'Modifier les employés'],
            ['name' => 'delete_employees', 'module' => 'employees', 'description' => 'Supprimer les employés'],
            ['name' => 'export_employees', 'module' => 'employees', 'description' => 'Exporter les employés'],

            // === CONGÉS & ABSENCES ===
            ['name' => 'view_leaves', 'module' => 'leaves', 'description' => 'Voir les congés'],
            ['name' => 'create_leaves', 'module' => 'leaves', 'description' => 'Créer des demandes de congé'],
            ['name' => 'edit_leaves', 'module' => 'leaves', 'description' => 'Modifier les congés'],
            ['name' => 'delete_leaves', 'module' => 'leaves', 'description' => 'Supprimer les congés'],
            ['name' => 'approve_leaves', 'module' => 'leaves', 'description' => 'Approuver les congés'],
            ['name' => 'reject_leaves', 'module' => 'leaves', 'description' => 'Rejeter les congés'],
            ['name' => 'view_absences', 'module' => 'leaves', 'description' => 'Voir les absences'],
            ['name' => 'manage_absences', 'module' => 'leaves', 'description' => 'Gérer les absences'],
            ['name' => 'view_attendances', 'module' => 'leaves', 'description' => 'Voir les pointages'],
            ['name' => 'manage_attendances', 'module' => 'leaves', 'description' => 'Gérer les pointages'],

            // === PAIE ===
            ['name' => 'view_payrolls', 'module' => 'payroll', 'description' => 'Voir les paies'],
            ['name' => 'create_payrolls', 'module' => 'payroll', 'description' => 'Créer des paies'],
            ['name' => 'edit_payrolls', 'module' => 'payroll', 'description' => 'Modifier les paies'],
            ['name' => 'delete_payrolls', 'module' => 'payroll', 'description' => 'Supprimer les paies'],
            ['name' => 'calculate_payrolls', 'module' => 'payroll', 'description' => 'Calculer les paies'],
            ['name' => 'generate_payslips', 'module' => 'payroll', 'description' => 'Générer les bulletins'],
            ['name' => 'export_payrolls', 'module' => 'payroll', 'description' => 'Exporter les paies'],

            // === DOCUMENTS ===
            ['name' => 'view_documents', 'module' => 'documents', 'description' => 'Voir les documents'],
            ['name' => 'create_documents', 'module' => 'documents', 'description' => 'Créer des documents'],
            ['name' => 'edit_documents', 'module' => 'documents', 'description' => 'Modifier les documents'],
            ['name' => 'delete_documents', 'module' => 'documents', 'description' => 'Supprimer les documents'],
            ['name' => 'download_documents', 'module' => 'documents', 'description' => 'Télécharger les documents'],
            ['name' => 'publish_documents', 'module' => 'documents', 'description' => 'Publier les documents'],

            // === ÉVALUATIONS ===
            ['name' => 'view_evaluations', 'module' => 'evaluations', 'description' => 'Voir les évaluations'],
            ['name' => 'create_evaluations', 'module' => 'evaluations', 'description' => 'Créer des évaluations'],
            ['name' => 'edit_evaluations', 'module' => 'evaluations', 'description' => 'Modifier les évaluations'],
            ['name' => 'delete_evaluations', 'module' => 'evaluations', 'description' => 'Supprimer les évaluations'],
            ['name' => 'validate_evaluations', 'module' => 'evaluations', 'description' => 'Valider les évaluations'],

            // === FORMATIONS ===
            ['name' => 'view_trainings', 'module' => 'trainings', 'description' => 'Voir les formations'],
            ['name' => 'create_trainings', 'module' => 'trainings', 'description' => 'Créer des formations'],
            ['name' => 'edit_trainings', 'module' => 'trainings', 'description' => 'Modifier les formations'],
            ['name' => 'delete_trainings', 'module' => 'trainings', 'description' => 'Supprimer les formations'],
            ['name' => 'manage_training_participants', 'module' => 'trainings', 'description' => 'Gérer les participants'],

            // === CONTRATS ===
            ['name' => 'view_contracts', 'module' => 'contracts', 'description' => 'Voir les contrats'],
            ['name' => 'create_contracts', 'module' => 'contracts', 'description' => 'Créer des contrats'],
            ['name' => 'edit_contracts', 'module' => 'contracts', 'description' => 'Modifier les contrats'],
            ['name' => 'delete_contracts', 'module' => 'contracts', 'description' => 'Supprimer les contrats'],
            ['name' => 'renew_contracts', 'module' => 'contracts', 'description' => 'Renouveler les contrats'],

            // === ASSURANCE SANTÉ & AYANTS DROIT ===
            ['name' => 'view_dependents', 'module' => 'health', 'description' => 'Voir les ayants droit'],
            ['name' => 'create_dependents', 'module' => 'health', 'description' => 'Créer des ayants droit'],
            ['name' => 'edit_dependents', 'module' => 'health', 'description' => 'Modifier les ayants droit'],
            ['name' => 'delete_dependents', 'module' => 'health', 'description' => 'Supprimer les ayants droit'],
            ['name' => 'issue_health_cards', 'module' => 'health', 'description' => 'Émettre cartes santé'],
            ['name' => 'activate_health_cards', 'module' => 'health', 'description' => 'Activer cartes santé'],
            ['name' => 'view_employee_cards', 'module' => 'health', 'description' => 'Voir les cartes employés'],
            ['name' => 'manage_employee_cards', 'module' => 'health', 'description' => 'Gérer les cartes employés'],

            // === RAPPORTS ===
            ['name' => 'view_reports', 'module' => 'reports', 'description' => 'Voir les rapports'],
            ['name' => 'generate_reports', 'module' => 'reports', 'description' => 'Générer des rapports'],
            ['name' => 'export_reports', 'module' => 'reports', 'description' => 'Exporter les rapports'],

            // === PARAMÈTRES ===
            ['name' => 'view_settings', 'module' => 'settings', 'description' => 'Voir les paramètres'],
            ['name' => 'edit_settings', 'module' => 'settings', 'description' => 'Modifier les paramètres'],
            ['name' => 'manage_roles', 'module' => 'settings', 'description' => 'Gérer les rôles'],
            ['name' => 'manage_permissions', 'module' => 'settings', 'description' => 'Gérer les permissions'],
            ['name' => 'manage_cache', 'module' => 'settings', 'description' => 'Gérer le cache'],

            // === STRUCTURE ORGANISATIONNELLE ===
            ['name' => 'manage_departments', 'module' => 'structure', 'description' => 'Gérer les départements'],
            ['name' => 'manage_services', 'module' => 'structure', 'description' => 'Gérer les services'],
            ['name' => 'manage_positions', 'module' => 'structure', 'description' => 'Gérer les postes'],

            // === GRILLE SALARIALE ===
            ['name' => 'view_salary_grids', 'module' => 'payroll', 'description' => 'Voir la grille salariale'],
            ['name' => 'create_salary_grids', 'module' => 'payroll', 'description' => 'Créer grille salariale'],
            ['name' => 'edit_salary_grids', 'module' => 'payroll', 'description' => 'Modifier grille salariale'],
            ['name' => 'delete_salary_grids', 'module' => 'payroll', 'description' => 'Supprimer grille salariale'],

            // === QUOTE-PARTS ===
            ['name' => 'view_quotparts', 'module' => 'payroll', 'description' => 'Voir les quote-parts'],
            ['name' => 'create_quotparts', 'module' => 'payroll', 'description' => 'Créer quote-parts'],
            ['name' => 'edit_quotparts', 'module' => 'payroll', 'description' => 'Modifier quote-parts'],
            ['name' => 'calculate_quotparts', 'module' => 'payroll', 'description' => 'Calculer quote-parts'],
            ['name' => 'approve_quotparts', 'module' => 'payroll', 'description' => 'Approuver quote-parts'],
            ['name' => 'distribute_quotparts', 'module' => 'payroll', 'description' => 'Distribuer quote-parts'],

            // === ÉVALUATIONS EMPLOYÉS (pour quote-parts) ===
            ['name' => 'create_employee_evaluations', 'module' => 'evaluations', 'description' => 'Créer évaluations employés'],
            ['name' => 'edit_employee_evaluations', 'module' => 'evaluations', 'description' => 'Modifier évaluations'],
            ['name' => 'approve_employee_evaluations', 'module' => 'evaluations', 'description' => 'Approuver évaluations'],

            // === ACTIVITÉS MÉDICALES ===
            ['name' => 'view_medical_activities', 'module' => 'payroll', 'description' => 'Voir activités médicales'],
            ['name' => 'create_medical_activities', 'module' => 'payroll', 'description' => 'Créer activités médicales'],
            ['name' => 'validate_medical_activities', 'module' => 'payroll', 'description' => 'Valider activités médicales'],

            // === TYPES DE CONTRATS ===
            ['name' => 'view_contract_types', 'module' => 'contracts', 'description' => 'Voir types de contrats'],
            ['name' => 'manage_contract_types', 'module' => 'contracts', 'description' => 'Gérer types de contrats'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                [
                    'module' => $permissionData['module'],
                    'description' => $permissionData['description'],
                    'guard_name' => 'web',
                ]
            );
        }

        // ============================================
        // CRÉATION DES RÔLES ET ATTRIBUTION
        // ============================================

        // 0. SUPER ADMIN - Accès absolu (un seul dans le système)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin'], ['guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all()); // TOUTES les permissions

        // 1. ADMIN - Accès complet SAUF gestion super_admin
        $admin = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        $adminPermissions = Permission::whereNotIn('name', [
            'manage_super_admin',
            'manage_admins',
            'manage_all_roles',
            'access_system_logs',
            'manage_database',
        ])->pluck('name')->toArray();
        $admin->syncPermissions($adminPermissions);

        // 2. DRH - Directeur des Ressources Humaines
        $drh = Role::firstOrCreate(['name' => 'drh'], ['guard_name' => 'web']);
        $drh->syncPermissions([
            // Utilisateurs (lecture seule)
            'view_users',
            // Employés
            'view_employees',
            'create_employees',
            'edit_employees',
            'export_employees',
            // Congés & Absences
            'view_leaves',
            'create_leaves',
            'edit_leaves',
            'approve_leaves',
            'reject_leaves',
            'view_absences',
            'manage_absences',
            'view_attendances',
            'manage_attendances',
            // Contrats
            'view_contracts',
            'create_contracts',
            'edit_contracts',
            'renew_contracts',
            // Documents
            'view_documents',
            'create_documents',
            'edit_documents',
            'download_documents',
            'publish_documents',
            // Évaluations
            'view_evaluations',
            'create_evaluations',
            'edit_evaluations',
            'validate_evaluations',
            // Formations
            'view_trainings',
            'create_trainings',
            'edit_trainings',
            'manage_training_participants',
            // Santé
            'view_dependents',
            'create_dependents',
            'edit_dependents',
            'issue_health_cards',
            'activate_health_cards',
            'view_employee_cards',
            'manage_employee_cards',
            // Structure
            'manage_departments',
            'manage_services',
            'manage_positions',
            // Rapports
            'view_reports',
            'generate_reports',
            'export_reports',
            // Paramètres (limité)
            'view_settings',
            'manage_cache',
        ]);

        // 3. DAF - Directeur Administratif et Financier
        $daf = Role::firstOrCreate(['name' => 'daf'], ['guard_name' => 'web']);
        $daf->syncPermissions([
            // Employés (lecture)
            'view_employees',
            // Paie (complet)
            'view_payrolls',
            'create_payrolls',
            'edit_payrolls',
            'calculate_payrolls',
            'generate_payslips',
            'export_payrolls',
            // Marchés Publics (complet)
            'view_procurement',
            'create_procurement',
            'edit_procurement',
            'approve_procurement',
            'manage_bids',
            // Documents
            'view_documents',
            'download_documents',
            'create_documents',
            // Rapports
            'view_reports',
            'generate_reports',
            'export_reports',
        ]);

        // 4. DG - Directeur Général
        $dg = Role::firstOrCreate(['name' => 'dg'], ['guard_name' => 'web']);
        $dg->syncPermissions([
            // Employés
            'view_employees',
            // Congés (validation finale)
            'view_leaves',
            'approve_leaves',
            // Paie (consultation)
            'view_payrolls',
            // Documents
            'view_documents',
            'download_documents',
            'publish_documents',
            // Évaluations (validation)
            'view_evaluations',
            'validate_evaluations',
            // Marchés (approbation)
            'view_procurement',
            'approve_procurement',
            // Rapports
            'view_reports',
            'generate_reports',
        ]);

        // 5. CHEF DE SERVICE
        $chefService = Role::firstOrCreate(['name' => 'chef_service'], ['guard_name' => 'web']);
        $chefService->syncPermissions([
            // Employés de son service
            'view_employees',
            // Congés (approbation premier niveau)
            'view_leaves',
            'approve_leaves',
            // Absences
            'view_absences',
            'view_attendances',
            // Documents
            'view_documents',
            'download_documents',
            // Évaluations (création pour son équipe)
            'view_evaluations',
            'create_evaluations',
            // Formations
            'view_trainings',
        ]);

        // 6. EMPLOYEE - Employé standard
        $standardUser = Role::firstOrCreate(['name' => 'standard_user'], ['guard_name' => 'web']);
        $standardUser->syncPermissions([
            // Congés (ses propres demandes)
            'create_leaves',
            'view_leaves',
            // Documents
            'view_documents',
            'download_documents',
            // Formations (consultation)
            'view_trainings',
        ]);

        // ============================================
        // CRÉATION DU SUPER ADMIN PAR DÉFAUT
        // ============================================

        $this->createSuperAdminUser();

        echo "✅ Rôles et permissions créés avec succès!\n";
        echo "   - Rôles créés: super_admin, admin, drh, daf, dg, chef_service, employee\n";
        echo "   - " . Permission::count() . " permissions créées\n";
        echo "   - Super Admin créé: adminkit@gmail.com\n";
    }

    /**
     * Créer l'utilisateur Super Admin par défaut
     */
    protected function createSuperAdminUser(): void
    {
        $superAdminUser = User::firstOrCreate(
            ['email' => 'adminkit@gmail.com'],
            [
                'name' => 'Super Administrateur',
                'password' => Hash::make('Admin@2025'), // Changez ce mot de passe!
                'email_verified_at' => now(),
            ]
        );

        // Retirer tous les autres rôles et assigner super_admin
        $superAdminUser->syncRoles(['super_admin']);

        echo "   ⚠️  IMPORTANT: Changez le mot de passe du Super Admin!\n";
        echo "   Email: adminkit@gmail.com\n";
        echo "   Password: Admin@2025\n";
    }
}
