<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_assignment_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Type d'affectation
            $table->enum('assignment_type', ['position', 'department', 'service', 'location', 'contract_type']);

            // Ancienne valeur
            $table->string('old_position_id')->nullable();
            $table->string('old_position_title')->nullable();
            $table->unsignedBigInteger('old_department_id')->nullable();
            $table->string('old_department_name')->nullable();
            $table->unsignedBigInteger('old_service_id')->nullable();
            $table->string('old_service_name')->nullable();

            // Nouvelle valeur
            $table->string('new_position_id')->nullable();
            $table->string('new_position_title')->nullable();
            $table->unsignedBigInteger('new_department_id')->nullable();
            $table->string('new_department_name')->nullable();
            $table->unsignedBigInteger('new_service_id')->nullable();
            $table->string('new_service_name')->nullable();

            // Détails du changement
            $table->date('effective_date'); // Date d'effet
            $table->date('end_date')->nullable(); // Date de fin (si temporaire)
            $table->boolean('is_temporary')->default(false); // Affectation temporaire ou définitive
            $table->text('reason')->nullable(); // Motif du changement
            $table->string('decision_number')->nullable(); // Numéro de décision
            $table->date('decision_date')->nullable(); // Date de la décision

            // Qui a fait le changement
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('old_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('new_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('old_service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('new_service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['employee_id', 'effective_date']);
            $table->index('assignment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_assignment_history');
    }
};
