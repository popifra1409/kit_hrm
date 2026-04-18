<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'hierarchical_level')) {
                $table->enum('hierarchical_level', [
                    'pca',              // Président Conseil d'Administration
                    'dg',               // Directeur Général
                    'dga',              // Directeur Général Adjoint
                    'directeur',        // Directeur (Direction)
                    'sous_directeur',   // Sous-Directeur ou Chef Département
                    'chef_service',     // Chef de Service
                    'major',            // Major (médical)
                    'chef_secteur',     // Chef de Secteur
                    'cadre',            // Cadre
                    'agent',            // Agent d'exécution
                    'stagiaire'         // Stagiaire
                ])->nullable()->after('name');
            }

            // Niveau numérique pour faciliter les comparaisons
            if (!Schema::hasColumn('positions', 'level_rank')) {
                $table->integer('level_rank')->nullable()->after('hierarchical_level')
                    ->comment('1=PCA, 2=DG, 3=DGA, 4=Directeur, 5=Sous-Dir, 6=Chef Service, 7=Major/Chef Secteur, 8=Cadre, 9=Agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['hierarchical_level', 'level_rank']);
        });
    }
};
