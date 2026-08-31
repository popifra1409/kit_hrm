<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. D'abord SUPPRIMER l'ancienne contrainte
        DB::statement("ALTER TABLE directions DROP CONSTRAINT IF EXISTS directions_type_check");

        // 2. MAINTENANT corriger les données
        DB::statement("UPDATE directions SET type = 'medical' WHERE name LIKE '%MEDICALE%'");
        DB::statement("UPDATE directions SET type = 'administrative' WHERE type = 'technique'");

        // 3. Ajouter la nouvelle contrainte
        DB::statement("ALTER TABLE directions ADD CONSTRAINT directions_type_check CHECK (type IN ('medical', 'administrative'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE directions DROP CONSTRAINT IF EXISTS directions_type_check");
    }
};
