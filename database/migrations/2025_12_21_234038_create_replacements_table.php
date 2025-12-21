<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('replacements', function (Blueprint $table) {
            $table->id();

            // Qui remplace qui
            $table->unsignedBigInteger('original_employee_id'); // Employé absent
            $table->unsignedBigInteger('replacement_employee_id'); // Remplaçant

            // Période de remplacement
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);

            // Motif du remplacement
            $table->enum('reason', [
                'leave',        // Congé
                'sick_leave',   // Maladie
                'maternity',    // Maternité
                'mission',      // Mission
                'training',     // Formation
                'other'         // Autre
            ]);

            // Détails de l'affectation temporaire
            $table->unsignedBigInteger('temporary_service_id')->nullable();
            $table->unsignedBigInteger('temporary_position_id')->nullable();
            $table->text('responsibilities')->nullable(); // Responsabilités confiées

            // Rémunération additionnelle
            $table->boolean('has_bonus')->default(false);
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->enum('bonus_type', ['fixed', 'percentage'])->default('fixed');

            // Validation
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Décision
            $table->string('decision_number')->nullable();
            $table->date('decision_date')->nullable();

            // Évaluation du remplacement
            $table->integer('performance_rating')->nullable(); // 1-5
            $table->text('performance_notes')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('original_employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('replacement_employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('temporary_service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('temporary_position_id')->references('id')->on('positions')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index(['original_employee_id', 'start_date']);
            $table->index(['replacement_employee_id', 'start_date']);
            $table->index('status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replacements');
    }
};
