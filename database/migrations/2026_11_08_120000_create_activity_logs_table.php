<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal d'activité : une ligne par action ou tentative, ajoutée uniquement.
     *
     * Les colonnes « actor_* », « subject_* » et « properties » sont des instantanés :
     * le journal doit rester lisible même après la suppression de l'agent, de l'employé
     * ou du dossier concerné.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // L'horodatage qui compte pour l'exploitation ; c'est le seul qu'on trie.
            $table->timestamp('occurred_at')->index();

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_matricule')->nullable();
            $table->string('actor_name')->nullable();

            // panel | api | public | console : d'où vient l'action.
            $table->string('context', 20)->default('panel')->index();
            // login, login_failed, census_submitted, updated, …
            $table->string('action', 60)->index();
            // success | failed | blocked : une tentative erronée est une ligne à part entière.
            $table->string('status', 20)->default('success')->index();

            // Sujet visé, sans clé étrangère : le journal survit à la suppression.
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();

            // En clair : le motif d'un refus, champ par champ si nécessaire.
            $table->text('reason')->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->index(['occurred_at', 'context']);
            $table->index(['actor_user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
