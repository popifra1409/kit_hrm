<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Période d'évaluation
            $table->enum('period_type', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('annual');
            $table->date('evaluation_date');
            $table->date('period_start_date');
            $table->date('period_end_date');

            // Évaluateurs
            $table->unsignedBigInteger('evaluator_id'); // Manager direct
            $table->unsignedBigInteger('validator_id')->nullable(); // DRH/DG

            // Critères d'évaluation (sur 5)
            $table->decimal('technical_skills', 3, 2)->default(0); // Compétences techniques
            $table->decimal('soft_skills', 3, 2)->default(0); // Compétences relationnelles
            $table->decimal('productivity', 3, 2)->default(0); // Productivité
            $table->decimal('quality_of_work', 3, 2)->default(0); // Qualité du travail
            $table->decimal('initiative', 3, 2)->default(0); // Initiative/Autonomie
            $table->decimal('teamwork', 3, 2)->default(0); // Travail d'équipe
            $table->decimal('punctuality', 3, 2)->default(0); // Ponctualité/Assiduité
            $table->decimal('adaptability', 3, 2)->default(0); // Adaptabilité
            $table->decimal('leadership', 3, 2)->default(0)->nullable(); // Leadership (si applicable)

            // Score global
            $table->decimal('overall_score', 4, 2)->default(0); // Moyenne générale
            $table->enum('rating', [
                'excellent',      // 4.5-5
                'very_good',      // 4-4.49
                'good',           // 3-3.99
                'satisfactory',   // 2-2.99
                'needs_improvement' // <2
            ])->nullable();

            // Points forts et axes d'amélioration
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();

            // Objectifs
            $table->text('previous_objectives_review')->nullable(); // Bilan objectifs précédents
            $table->text('new_objectives')->nullable(); // Nouveaux objectifs

            // Formation recommandée
            $table->text('training_recommendations')->nullable();

            // Commentaires
            $table->text('evaluator_comments')->nullable();
            $table->text('employee_comments')->nullable(); // Auto-évaluation
            $table->text('validator_comments')->nullable();

            // Statut
            $table->enum('status', [
                'draft',          // Brouillon
                'pending_employee', // En attente validation employé
                'pending_validator', // En attente validation DRH/DG
                'validated',      // Validée
                'contested'       // Contestée
            ])->default('draft');

            // Signatures
            $table->timestamp('employee_signed_at')->nullable();
            $table->timestamp('evaluator_signed_at')->nullable();
            $table->timestamp('validator_signed_at')->nullable();

            // Documents
            $table->string('evaluation_document')->nullable(); // PDF signé

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('validator_id')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index(['employee_id', 'evaluation_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_evaluations');
    }
};
