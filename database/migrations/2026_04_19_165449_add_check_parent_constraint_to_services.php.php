<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint
                WHERE conname = 'check_parent'
            ) THEN
                ALTER TABLE services
                ADD CONSTRAINT check_parent
                CHECK (
                    (department_id IS NOT NULL AND sub_direction_id IS NULL) OR
                    (department_id IS NULL AND sub_direction_id IS NOT NULL)
                )
                NOT VALID;
            END IF;
        END
        $$;
    ");
    }

    public function down()
    {
        DB::statement("
            ALTER TABLE services
            DROP CONSTRAINT IF EXISTS check_parent
        ");
    }
};
