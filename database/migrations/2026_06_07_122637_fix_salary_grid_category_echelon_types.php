<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('salary_grids', function (Blueprint $table) {
            // PostgreSQL : Utiliser du SQL brut pour changer le type de colonne
            DB::statement('ALTER TABLE salary_grids ALTER COLUMN category TYPE varchar(10)');
            DB::statement('ALTER TABLE salary_grids ALTER COLUMN echelon TYPE varchar(10)');
        });

        echo "✅ Colonnes category et echelon converties en varchar(10)\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_grids', function (Blueprint $table) {
            // Revenir au type integer
            DB::statement('ALTER TABLE salary_grids ALTER COLUMN category TYPE integer USING (category::integer)');
            DB::statement('ALTER TABLE salary_grids ALTER COLUMN echelon TYPE integer USING (echelon::integer)');
        });

        echo "✅ Colonnes category et echelon reconverties en integer\n";
    }
};
