<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS services_type_check');

        DB::statement("
            ALTER TABLE services
            ADD CONSTRAINT services_type_check
            CHECK (type::text = ANY (ARRAY['medical', 'administrative', 'technical', 'support']::text[]))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS services_type_check');

        DB::statement("
            ALTER TABLE services
            ADD CONSTRAINT services_type_check
            CHECK (type::text = ANY (ARRAY['medical', 'administrative']::text[]))
        ");
    }
};