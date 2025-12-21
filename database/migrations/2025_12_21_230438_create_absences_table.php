<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Type d'absence
            $table->enum('type', [
                'exceptional',      // Permission exceptionnelle (événements familiaux)
                'personal',         // Convenance personnelle
                'medical',          // Repos médical/maladie
                'late_arrival',     // Retard
                'early_departure',  // Départ anticipé
                'administrative'    // Autorisation administrative
            ]);

            // Période
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 5, 2)->default(0); // Durée en heures
            $table->boolean('is_full_day')->default(false); // Absence journée complète

            // Motif
            $table->text('reason');
            $table->string('justification_document')->nullable(); // Certificat médical, etc.

            // Validation
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Déduction sur paie
            $table->boolean('is_paid')->default(true);
            $table->decimal('deduction_amount', 10, 2)->default(0);

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index(['employee_id', 'date']);
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
