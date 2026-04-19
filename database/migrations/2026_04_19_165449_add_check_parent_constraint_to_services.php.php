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
            -- 1. Supprimer ancienne contrainte si elle existe
            IF EXISTS (
                SELECT 1
                FROM pg_constraint
                WHERE conname = 'check_parent'
            ) THEN
                ALTER TABLE services DROP CONSTRAINT check_parent;
            END IF;

            -- 2. Ajouter nouvelle contrainte progressive
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint
                WHERE conname = 'check_sub_direction'
            ) THEN
                ALTER TABLE services
                ADD CONSTRAINT check_sub_direction
                CHECK (
                    sub_direction_id IS NOT NULL
                    OR department_id IS NOT NULL
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
        DO $$
        BEGIN
            -- Supprimer nouvelle contrainte
            IF EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'check_sub_direction'
            ) THEN
                ALTER TABLE services DROP CONSTRAINT check_sub_direction;
            END IF;

            -- Restaurer ancienne contrainte (optionnel)
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'check_parent'
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
};
