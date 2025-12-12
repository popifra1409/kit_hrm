<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer d'abord les contraintes de clés étrangères
        DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_position_id_foreign');
        DB::statement('ALTER TABLE employee_affectations DROP CONSTRAINT IF EXISTS employee_affectations_position_id_foreign');

        // Ensuite supprimer la table
        Schema::dropIfExists('positions');
    }

    public function down(): void
    {
        // Rien à faire
    }
};
