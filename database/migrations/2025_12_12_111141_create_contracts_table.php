<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('employee_id')->constrained()->onDelete('cascade');
            //$table->foreignId('contract_type_id')->constrained();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('contract_type_id');
            $table->string('contract_number')->unique();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->text('terms')->nullable(); // Clauses du contrat
            $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'renewed'])->default('draft');
            $table->text('termination_reason')->nullable();
            $table->date('termination_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
