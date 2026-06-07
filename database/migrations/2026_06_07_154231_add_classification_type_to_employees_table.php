<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('classification_type', ['cameroon', 'numeric'])
                ->default('numeric')
                ->after('id');
        });

        // ✅ Remplir les données existantes
        DB::statement("UPDATE employees SET classification_type = 'numeric' WHERE classification_type IS NULL");
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('classification_type');
        });
    }
};
