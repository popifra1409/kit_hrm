<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // DROP CONSTRAINT : PostgreSQL uniquement (sous sqlite les CHECK inline ne sont
        // pas séparables ; les tests n'utilisent pas ces colonnes).
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE departments DROP CONSTRAINT IF EXISTS departments_type_check");
        DB::statement("ALTER TABLE services DROP CONSTRAINT IF EXISTS check_parent");
        DB::statement("ALTER TABLE services DROP CONSTRAINT IF EXISTS check_sub_direction");
    }

    public function down(): void
    {
        // Les contraintes seront restaurées par les migrations originales
    }
};
