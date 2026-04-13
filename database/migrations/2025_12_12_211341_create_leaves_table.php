<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days'); // Nombre total de jours demandés
            $table->text('reason')->nullable();
            $table->string('document_path')->nullable(); // Chemin du justificatif

            // Workflow de validation
            $table->enum('status', [
                'pending',        // En attente
                'approved_n1',    // Approuvé niveau 1 (chef service)
                'approved_n2',    // Approuvé niveau 2 (DRH)
                'approved',       // Approuvé final
                'rejected',       // Rejeté
                'cancelled'       // Annulé
            ])->default('pending');

            $table->unsignedBigInteger('approved_by_n1')->nullable(); // Chef de service
            $table->unsignedBigInteger('approved_by_n2')->nullable(); // DRH
            $table->timestamp('approved_at_n1')->nullable();
            $table->timestamp('approved_at_n2')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Critères d'évaluation (selon cahier des charges)
            $table->decimal('anciennete_score', 5, 2)->nullable(); // Score ancienneté
            $table->decimal('discipline_score', 5, 2)->nullable(); // Score discipline
            $table->decimal('children_score', 5, 2)->nullable(); // Score enfants < 6 ans
            $table->decimal('total_score', 5, 2)->nullable(); // Score total

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
