<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer l'ancienne contrainte si elle existe
        DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_personnel_type_check");

        // Modifier le type de colonne
        DB::statement("ALTER TABLE employees ALTER COLUMN personnel_type TYPE VARCHAR(20)");

        // Mettre à jour les anciennes valeurs
        DB::table('employees')
            ->where('personnel_type', 'medical')
            ->update(['personnel_type' => 'soignant']);

        DB::table('employees')
            ->whereIn('personnel_type', ['administrative', 'support', 'technical'])
            ->update(['personnel_type' => 'non_soignant']);

        DB::table('employees')
            ->where('personnel_type', 'paramedical')
            ->update(['personnel_type' => 'paramedical']);

        // Ajouter la nouvelle contrainte
        DB::statement("
            ALTER TABLE employees 
            ADD CONSTRAINT employees_personnel_type_check 
            CHECK (personnel_type IN ('soignant', 'non_soignant', 'paramedical', 'autres'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_personnel_type_check");

        DB::statement("
            ALTER TABLE employees 
            ADD CONSTRAINT employees_personnel_type_check 
            CHECK (personnel_type IN ('medical', 'paramedical', 'administrative', 'technical', 'support'))
        ");
    }
};
