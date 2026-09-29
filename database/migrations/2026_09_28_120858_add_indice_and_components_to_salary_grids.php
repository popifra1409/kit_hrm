<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_grids', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_grids', 'indice')) {
                $table->unsignedInteger('indice')->nullable()->after('echelon');
            }
            if (!Schema::hasColumn('salary_grids', 'salaire_indiciaire_brut')) {
                $table->decimal('salaire_indiciaire_brut', 12, 2)->nullable()->after('indice');
            }
            if (!Schema::hasColumn('salary_grids', 'complement_forfaitaire')) {
                $table->decimal('complement_forfaitaire', 12, 2)->nullable()->after('salaire_indiciaire_brut');
            }
            if (!Schema::hasColumn('salary_grids', 'indemnite_logement')) {
                $table->decimal('indemnite_logement', 12, 2)->nullable()->after('complement_forfaitaire');
            }
        });

        // Le salaire de base n'est plus obligatoire en base : pour les fonctionnaires il est
        // dérivé de la grille indiciaire officielle (voir SalaryGrid::booted()).
        Schema::table('salary_grids', function (Blueprint $table) {
            $table->decimal('base_salary', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('salary_grids', function (Blueprint $table) {
            $table->dropColumn(['indice', 'salaire_indiciaire_brut', 'complement_forfaitaire', 'indemnite_logement']);
        });
    }
};
