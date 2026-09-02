<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ MIGRATION IRRÉVERSIBLE (perte de données sur les colonnes/table supprimées).
 * Assurez-vous d'avoir une sauvegarde de la base avant de lancer `php artisan migrate`.
 *
 * Pré-requis vérifiés avant d'écrire cette migration :
 * - employees.position_id : 404/404 déjà migrés vers qualification_id/job_title_id
 * - employee_affectations.position_id : migré vers qualification_id (colonne ajoutée précédemment)
 * - replacements.temporary_position_id : migré vers temporary_qualification_id
 * - Tout le code applicatif ne référence plus position_id/Position (grep vide, tests OK)
 */
return new class extends Migration
{
    public function up(): void
    {
        // employees : colonnes obsolètes
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'position_id')) {
                $table->dropConstrainedForeignId('position_id');
            }
            if (Schema::hasColumn('employees', 'category_current')) {
                $table->dropColumn('category_current');
            }
            if (Schema::hasColumn('employees', 'qualification')) {
                $table->dropColumn('qualification'); // ancien champ texte, remplacé par qualification_id
            }
            if (Schema::hasColumn('employees', 'employment_type')) {
                $table->dropColumn('employment_type'); // remplacé par administrative_status
            }
        });

        // employee_affectations : colonne obsolète
        if (Schema::hasTable('employee_affectations') && Schema::hasColumn('employee_affectations', 'position_id')) {
            Schema::table('employee_affectations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('position_id');
            });
        }

        // replacements : colonne obsolète
        if (Schema::hasTable('replacements') && Schema::hasColumn('replacements', 'temporary_position_id')) {
            Schema::table('replacements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('temporary_position_id');
            });
        }

        // Table positions elle-même
        Schema::dropIfExists('positions');
    }

    public function down(): void
    {
        // Pas de rollback fidèle possible (les données de `positions` seraient perdues).
        // Restaurez depuis une sauvegarde si besoin de revenir en arrière.
        if (!Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('description')->nullable();
                $table->integer('level_rank')->nullable();
                $table->boolean('is_managerial')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }
};
