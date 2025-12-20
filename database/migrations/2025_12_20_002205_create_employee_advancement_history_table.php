<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_advancement_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Type d'avancement
            $table->enum('advancement_type', ['echelon', 'category', 'grade', 'salary_adjustment']);

            // Ancienne situation
            $table->integer('old_category')->nullable();
            $table->integer('old_echelon')->nullable();
            $table->string('old_grade')->nullable();
            $table->decimal('old_salary', 15, 2)->nullable();

            // Nouvelle situation
            $table->integer('new_category')->nullable();
            $table->integer('new_echelon')->nullable();
            $table->string('new_grade')->nullable();
            $table->decimal('new_salary', 15, 2)->nullable();

            // Détails de l'avancement
            $table->date('effective_date'); // Date d'effet
            $table->boolean('is_automatic')->default(false); // Avancement automatique ou exceptionnel
            $table->text('reason')->nullable(); // Motif (ancienneté, mérite, etc.)
            $table->string('decision_number')->nullable(); // Numéro de décision
            $table->date('decision_date')->nullable(); // Date de la décision

            // Documents
            $table->string('decision_document_path')->nullable(); // PDF de la décision

            // Qui a validé
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['employee_id', 'effective_date']);
            $table->index('advancement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_advancement_history');
    }
};
