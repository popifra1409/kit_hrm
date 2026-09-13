<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $daf = Role::where('name', 'daf')->first();

        if (!$daf) {
            echo "ℹ️  Aucun rôle 'daf' trouvé — rien à fusionner.\n";
            return;
        }

        $daaf = Role::firstOrCreate(['name' => 'daaf'], ['guard_name' => 'web']);

        if ($daaf->permissions()->count() === 0) {
            $daaf->syncPermissions($daf->permissions);
        }

        $users = $daf->users;
        foreach ($users as $user) {
            $user->assignRole('daaf');
            $user->removeRole('daf');
        }

        echo "✅ {$users->count()} utilisateur(s) réassigné(s) de 'daf' vers 'daaf'.\n";

        $daf->delete();
        echo "✅ Rôle 'daf' supprimé.\n";
    }

    public function down(): void
    {
        // Fusion irréversible par design — pas de retour arrière automatique.
    }
};
    