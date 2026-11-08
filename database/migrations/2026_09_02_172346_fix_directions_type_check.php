<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ARRAY[]::text[] / DROP CONSTRAINT : PostgreSQL uniquement.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE directions DROP CONSTRAINT IF EXISTS directions_type_check');

        DB::statement("
            ALTER TABLE directions
            ADD CONSTRAINT directions_type_check
            CHECK (type::text = ANY (ARRAY['medical', 'administrative', 'technique', 'support']::text[]))
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE directions DROP CONSTRAINT IF EXISTS directions_type_check');

        // Revient à l'ancienne contrainte (attention : échouera si des lignes
        // 'technique' ou 'support' existent déjà à ce moment-là).
        DB::statement("
            ALTER TABLE directions
            ADD CONSTRAINT directions_type_check
            CHECK (type::text = ANY (ARRAY['medical', 'administrative']::text[]))
        ");
    }
};
