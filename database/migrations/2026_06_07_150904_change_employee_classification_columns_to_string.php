<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Changer category_number et echelon_number de integer à string
            DB::statement('ALTER TABLE employees ALTER COLUMN category_number TYPE varchar(10)');
            DB::statement('ALTER TABLE employees ALTER COLUMN echelon_number TYPE varchar(10)');
        });

        echo "✅ Colonnes category_number et echelon_number converties en varchar(10)\n";
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Revenir au type integer
            DB::statement('ALTER TABLE employees ALTER COLUMN category_number TYPE integer USING (category_number::integer)');
            DB::statement('ALTER TABLE employees ALTER COLUMN echelon_number TYPE integer USING (echelon_number::integer)');
        });

        echo "✅ Colonnes category_number et echelon_number reconverties en integer\n";
    }
};
