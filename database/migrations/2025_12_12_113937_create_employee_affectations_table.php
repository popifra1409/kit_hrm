<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_affectations', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('employee_id')->constrained()->onDelete('cascade');
            //$table->foreignId('service_id')->constrained();
            //$table->foreignId('position_id')->nullable()->constrained();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('reason')->nullable(); // Raison de l'affectation/mutation
            $table->string('decision_number')->nullable(); // N° de décision
            $table->boolean('is_current')->default(false); // Affectation actuelle
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_affectations');
    }
};
