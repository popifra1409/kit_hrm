<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $standardUser = Role::where('name', 'standard_user')->first();

        if (!$standardUser) {
            echo "ℹ️  Aucun rôle 'standard_user' trouvé — rien à fusionner.\n";
            return;
        }

        $employee = Role::firstOrCreate(['name' => 'employee'], ['guard_name' => 'web']);

        if ($employee->permissions()->count() === 0) {
            $employee->syncPermissions($standardUser->permissions);
        }

        $users = $standardUser->users;
        foreach ($users as $user) {
            $user->assignRole('employee');
            $user->removeRole('standard_user');
        }

        echo "✅ {$users->count()} utilisateur(s) réassigné(s) de 'standard_user' vers 'employee'.\n";

        $standardUser->delete();
        echo "✅ Rôle 'standard_user' supprimé.\n";
    }

    public function down(): void
    {
        // Fusion irréversible par design — pas de retour arrière automatique.
    }
};
