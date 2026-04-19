<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Supprimer la contrainte existante (si elle existe)
        DB::statement("ALTER TABLE services DROP CONSTRAINT IF EXISTS check_parent");

        echo "🔍 Vérification des services...\n";

        // 2. Identifier et corriger les services orphelins (sans rattachement)
        $orphans = DB::table('services')
            ->whereNull('department_id')
            ->whereNull('sub_direction_id')
            ->get();

        if ($orphans->count() > 0) {
            echo "⚠️ {$orphans->count()} services sans rattachement détectés\n";

            // Rattacher au premier département disponible
            $defaultDept = DB::table('departments')->orderBy('id')->first();

            if ($defaultDept) {
                DB::table('services')
                    ->whereNull('department_id')
                    ->whereNull('sub_direction_id')
                    ->update(['department_id' => $defaultDept->id]);

                echo "✅ Services orphelins rattachés au département ID: {$defaultDept->id}\n";
            } else {
                echo "❌ Aucun département disponible pour rattachement\n";
            }
        } else {
            echo "✅ Aucun service orphelin détecté\n";
        }

        // 3. Corriger les services avec double rattachement
        $doubles = DB::table('services')
            ->whereNotNull('department_id')
            ->whereNotNull('sub_direction_id')
            ->get();

        if ($doubles->count() > 0) {
            echo "⚠️ {$doubles->count()} services avec double rattachement\n";

            // Conserver department_id, supprimer sub_direction_id
            DB::table('services')
                ->whereNotNull('department_id')
                ->whereNotNull('sub_direction_id')
                ->update(['sub_direction_id' => null]);

            echo "✅ Double rattachement résolu (conservé department_id)\n";
        } else {
            echo "✅ Aucun double rattachement détecté\n";
        }

        // 4. Vérification finale
        $invalid = DB::table('services')
            ->where(function ($q) {
                $q->whereNull('department_id')->whereNull('sub_direction_id');
            })
            ->orWhere(function ($q) {
                $q->whereNotNull('department_id')->whereNotNull('sub_direction_id');
            })
            ->count();

        if ($invalid > 0) {
            echo "❌ Il reste {$invalid} services invalides\n";
            throw new \Exception("Impossible d'appliquer la contrainte - données invalides");
        }

        // 5. Appliquer la contrainte
        DB::statement("
            ALTER TABLE services 
            ADD CONSTRAINT check_parent 
            CHECK (
                (department_id IS NOT NULL AND sub_direction_id IS NULL) OR
                (department_id IS NULL AND sub_direction_id IS NOT NULL)
            )
        ");

        echo "✅ Contrainte check_parent appliquée avec succès\n";
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE services DROP CONSTRAINT IF EXISTS check_parent");
        echo "✅ Contrainte check_parent supprimée\n";
    }
};
