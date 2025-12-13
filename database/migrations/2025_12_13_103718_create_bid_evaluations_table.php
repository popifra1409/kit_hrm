<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bid_id');
            $table->unsignedBigInteger('evaluator_id'); // User évaluateur

            // Critères d'évaluation (selon grille ARMP)
            $table->decimal('technical_score', 5, 2)->nullable(); // Score technique (0-70)
            $table->decimal('financial_score', 5, 2)->nullable(); // Score financier (0-30)
            $table->decimal('experience_score', 5, 2)->nullable(); // Expérience
            $table->decimal('capacity_score', 5, 2)->nullable(); // Capacité
            $table->decimal('methodology_score', 5, 2)->nullable(); // Méthodologie
            $table->decimal('total_score', 5, 2)->nullable(); // Score total (0-100)

            // Avis
            $table->enum('recommendation', [
                'approve',        // Recommandé
                'conditional',    // Sous réserve
                'reject'          // Non recommandé
            ])->nullable();

            $table->text('strengths')->nullable(); // Points forts
            $table->text('weaknesses')->nullable(); // Points faibles
            $table->text('comments')->nullable();

            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid_evaluations');
    }
};
