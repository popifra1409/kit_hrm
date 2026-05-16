<?php

require __DIR__ . '/vendor/autoload.php';

$policies = [
    // Structure Organisationnelle
    'Direction' => ['manage_departments', 'Directeur, DRH, Admin'],
    'SubDirection' => ['manage_services', 'Directeur, DRH, Admin'],
    'Sector' => ['manage_services', 'DRH, Admin'],
    'MedicalDepartment' => ['manage_departments', 'DRH, Admin, Chef Service'],

    // Documents
    'DocumentCategory' => ['manage_documents', 'DRH, DAF, Admin'],

    // Avancements & Carrière
    'Advancement' => ['edit_employees', 'DRH, Admin'],
    'EmployeeAdvancementHistory' => ['view_employees', 'DRH, Admin, Chef Service'],
    'EmployeeAssignmentHistory' => ['view_employees', 'DRH, Admin, Chef Service'],
    'EmployeeAffectation' => ['edit_employees', 'DRH, Admin'],

    // Congés & Absences
    'LeaveBalance' => ['view_leaves', 'DRH, Chef Service'],
    'LeaveType' => ['manage_absences', 'DRH, Admin'],
    'Absence' => ['manage_absences', 'DRH, Chef Service'],
    'Replacement' => ['manage_absences', 'DRH, Chef Service'],
    'Attendance' => ['manage_attendances', 'DRH, Chef Service'],

    // Paie & Quote-Parts
    'Payroll' => ['view_payrolls', 'DAF, DRH'],
    'PayrollItem' => ['edit_payrolls', 'DAF'],
    'RevenueDeclaration' => ['view_quotparts', 'DAF, DRH'],
    'QuotpartPeriod' => ['manage_quotparts', 'DAF, Admin'],
    'QuotpartDistribution' => ['distribute_quotparts', 'DAF, DG'],
    'QuotpartDeductionType' => ['manage_quotparts', 'DAF, Admin'],
    'QuotpartParameter' => ['manage_quotparts', 'DAF, Admin'],
    'MedicalActivity' => ['create_medical_activities', 'Personnel Soignant, Chef Service'],

    // Évaluations
    'EvaluationCriterion' => ['manage_evaluations', 'DRH, Admin'],

    // Cartes & CNPS
    'EmployeeCard' => ['manage_employee_cards', 'DRH, Admin'],
    'CnpsPreRegistration' => ['edit_employees', 'DRH, DAF'],

    // Signatures & Système
    'Signatory' => ['manage_settings', 'DG, Admin'],
    'SystemSetting' => ['edit_settings', 'Super Admin, Admin'],
    'NotificationTemplate' => ['manage_settings', 'Admin'],
];

$policyTemplate = <<<'PHP'
<?php

namespace App\Policies;

use App\Models\{MODEL};
use App\Models\User;

class {MODEL}Policy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('{VIEW_PERMISSION}');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, {MODEL} ${VARIABLE}): bool
    {
        return $user->can('{VIEW_PERMISSION}');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('{CREATE_PERMISSION}');
    }

    /**
     * Modifier
     */
    public function update(User $user, {MODEL} ${VARIABLE}): bool
    {
        return $user->can('{EDIT_PERMISSION}');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, {MODEL} ${VARIABLE}): bool
    {
        return $user->can('{DELETE_PERMISSION}');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, {MODEL} ${VARIABLE}): bool
    {
        return $user->can('{EDIT_PERMISSION}');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, {MODEL} ${VARIABLE}): bool
    {
        return $user->hasRole('super_admin') && $user->can('{DELETE_PERMISSION}');
    }
}
PHP;

echo "🚀 GÉNÉRATION DES POLICIES\n\n";

foreach ($policies as $model => $config) {
    $permission = $config[0];
    $roles = $config[1];

    $variable = lcfirst($model);

    // Déterminer les permissions
    $viewPermission = str_replace('manage_', 'view_', $permission);
    $createPermission = str_replace(['view_', 'manage_'], 'create_', $permission);
    $editPermission = str_replace(['view_', 'manage_', 'create_'], 'edit_', $permission);
    $deletePermission = str_replace(['view_', 'manage_', 'create_', 'edit_'], 'delete_', $permission);

    $policyContent = str_replace(
        ['{MODEL}', '{VARIABLE}', '{VIEW_PERMISSION}', '{CREATE_PERMISSION}', '{EDIT_PERMISSION}', '{DELETE_PERMISSION}'],
        [$model, $variable, $viewPermission, $createPermission, $editPermission, $deletePermission],
        $policyTemplate
    );

    $filename = "app/Policies/{$model}Policy.php";

    if (file_exists($filename)) {
        echo "⏭️  Existe déjà : {$model}Policy\n";
    } else {
        file_put_contents($filename, $policyContent);
        echo "✅ Créé : {$model}Policy (Rôles: {$roles})\n";
    }
}

echo "\n✅ GÉNÉRATION TERMINÉE !\n";
echo "📋 Prochaine étape : Mettre à jour AuthServiceProvider\n";
