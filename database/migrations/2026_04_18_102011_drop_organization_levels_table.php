<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Supprimer d'abord la contrainte dans employee_hierarchies
        if (Schema::hasTable('employee_hierarchies')) {
            Schema::table('employee_hierarchies', function (Blueprint $table) {
                if (Schema::hasColumn('employee_hierarchies', 'organization_level_id')) {
                    $table->dropForeign(['organization_level_id']);
                    $table->dropColumn('organization_level_id');
                }
            });
        }

        // 2. Maintenant on peut supprimer la table organization_levels
        Schema::dropIfExists('organization_levels');
    }

    public function down(): void
    {
        // Recréer la table si rollback (optionnel)
        Schema::create('organization_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('hierarchy_level');
            $table->string('branch');
            $table->timestamps();
        });

        // Recréer la colonne dans employee_hierarchies
        if (Schema::hasTable('employee_hierarchies')) {
            Schema::table('employee_hierarchies', function (Blueprint $table) {
                $table->foreignId('organization_level_id')
                    ->nullable()
                    ->constrained('organization_levels')
                    ->nullOnDelete();
            });
        }
    }
};
