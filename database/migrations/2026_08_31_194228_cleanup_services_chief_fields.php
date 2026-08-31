<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Supprimer les colonnes redondantes (elles existent toutes!)
            if (Schema::hasColumn('services', 'head_of_service_id')) {
                $table->dropColumn('head_of_service_id');
            }

            if (Schema::hasColumn('services', 'deputy_director_id')) {
                $table->dropColumn('deputy_director_id');
            }

            if (Schema::hasColumn('services', 'service_head_id')) {
                $table->dropColumn('service_head_id');
            }
        });
    }

    public function down(): void
    {
        // Pas de rollback
    }
};
