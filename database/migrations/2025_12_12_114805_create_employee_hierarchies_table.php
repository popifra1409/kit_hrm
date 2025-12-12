<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_hierarchies', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            // $table->foreignId('organization_level_id')->constrained();
            // $table->foreignId('superior_id')->nullable()->constrained('employees'); // Supérieur hiérarchique
            // $table->foreignId('department_id')->nullable()->constrained();
            // $table->foreignId('medical_department_id')->nullable()->constrained();
            // $table->foreignId('service_id')->nullable()->constrained();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('organization_level_id');
            $table->unsignedBigInteger('superior_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('medical_department_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->string('appointment_decision')->nullable(); // N° de décision de nomination
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_hierarchies');
    }
};
