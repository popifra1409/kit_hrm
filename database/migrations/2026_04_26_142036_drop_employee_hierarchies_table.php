<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('employee_hierarchies');
    }

    public function down(): void
    {
        // Recréer la table si rollback
        Schema::create('employee_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('superior_id')->nullable()->constrained('employees');
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('medical_department_id')->nullable()->constrained('departments');
            $table->foreignId('service_id')->nullable()->constrained();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->string('appointment_decision')->nullable();
            $table->timestamps();
        });
    }
};
