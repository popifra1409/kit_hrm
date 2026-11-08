<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // CHECK / ALTER COLUMN ... TYPE : dialecte PostgreSQL uniquement. Sous sqlite (les
        // tests repartent d'une base vide), il n'y a ni contrainte ni données à normaliser.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 1. Supprimer l'ancienne contrainte
        DB::statement("ALTER TABLE departments DROP CONSTRAINT IF EXISTS departments_type_check");

        // 2. S'assurer que la colonne est de type VARCHAR
        DB::statement("ALTER TABLE departments ALTER COLUMN type TYPE VARCHAR(20)");

        // 3. Mettre à jour les valeurs existantes qui ne sont pas conformes
        DB::table('departments')
            ->whereNotIn('type', ['medical', 'surgical', 'diagnostic', 'support'])
            ->update(['type' => 'medical']); // Valeur par défaut

        // 4. Convertir les types null en 'medical' par défaut
        DB::table('departments')
            ->whereNull('type')
            ->update(['type' => 'medical']);

        // 5. Ajouter la nouvelle contrainte
        DB::statement("
            ALTER TABLE departments 
            ADD CONSTRAINT departments_type_check 
            CHECK (type IN ('medical', 'surgical', 'diagnostic', 'support'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Supprimer la contrainte
        DB::statement("ALTER TABLE departments DROP CONSTRAINT IF EXISTS departments_type_check");
    }
};
