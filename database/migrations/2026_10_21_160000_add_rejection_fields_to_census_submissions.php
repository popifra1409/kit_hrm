<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un rejet ne dit plus seulement « ce n'est pas bon » : l'agent coche les champs
     * que l'employé doit corriger, pour que le formulaire web les mette en évidence
     * et rouvre l'employé sur la bonne étape.
     */
    public function up(): void
    {
        Schema::table('census_submissions', function (Blueprint $table) {
            $table->json('career_rejection_fields')->nullable();
            $table->json('solde_rejection_fields')->nullable();
            $table->json('rejection_fields')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('census_submissions', function (Blueprint $table) {
            $table->dropColumn(['career_rejection_fields', 'solde_rejection_fields', 'rejection_fields']);
        });
    }
};
