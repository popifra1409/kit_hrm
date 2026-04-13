<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();

            // Informations de base
            $table->string('title');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();

            // Type et catégorie
            $table->enum('type', [
                'internal',      // Formation interne
                'external',      // Formation externe
                'online',        // Formation en ligne
                'workshop',      // Atelier
                'seminar',       // Séminaire
                'certification'  // Certification
            ]);

            $table->string('category')->nullable(); // Informatique, Santé, Management, etc.

            // Formateur/Organisme
            $table->string('trainer_name')->nullable();
            $table->string('training_organization')->nullable();
            $table->text('trainer_bio')->nullable();

            // Planning
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_hours')->default(0); // Durée en heures
            $table->integer('duration_days')->default(0); // Durée en jours

            // Localisation
            $table->string('location')->nullable();
            $table->string('room')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('online_link')->nullable();

            // Participants
            $table->integer('max_participants')->nullable();
            $table->integer('min_participants')->default(1);

            // Coûts
            $table->decimal('cost_per_participant', 10, 2)->default(0);
            $table->decimal('total_budget', 12, 2)->default(0);
            $table->string('budget_source')->nullable(); // Source du financement

            // Objectifs et prérequis
            $table->text('objectives')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('program')->nullable(); // Programme détaillé

            // Matériel
            $table->text('materials_needed')->nullable();
            $table->text('materials_provided')->nullable();

            // Évaluation
            $table->boolean('has_evaluation')->default(false);
            $table->boolean('has_certificate')->default(false);
            $table->string('certificate_template')->nullable();

            // Statut
            $table->enum('status', [
                'planned',       // Planifiée
                'registration_open', // Inscriptions ouvertes
                'registration_closed', // Inscriptions fermées
                'in_progress',   // En cours
                'completed',     // Terminée
                'cancelled'      // Annulée
            ])->default('planned');

            // Responsable
            $table->unsignedBigInteger('coordinator_id')->nullable();

            // Documents
            $table->string('syllabus_document')->nullable();
            $table->string('report_document')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('coordinator_id')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index('start_date');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
