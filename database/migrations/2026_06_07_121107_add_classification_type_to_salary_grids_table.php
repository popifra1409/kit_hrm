<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_grids', function (Blueprint $table) {
            $table->enum('classification_type', ['cameroon', 'numeric'])
                ->default('numeric')
                ->after('id')
                ->comment('Type: cameroon (A1, B2) ou numeric (1-12)');
        });
    }

    public function down(): void
    {
        Schema::table('salary_grids', function (Blueprint $table) {
            $table->dropColumn('classification_type');
        });
    }
};
