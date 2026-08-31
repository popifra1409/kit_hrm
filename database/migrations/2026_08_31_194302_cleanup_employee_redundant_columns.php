<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Supprimer les colonnes redondantes
            if (Schema::hasColumn('employees', 'category_current')) {
                $table->dropColumn('category_current');
            }
            if (Schema::hasColumn('employees', 'qualification')) {
                $table->dropColumn('qualification');
            }
        });
    }

    public function down(): void
    {
        // Pour rollback
    }
};
