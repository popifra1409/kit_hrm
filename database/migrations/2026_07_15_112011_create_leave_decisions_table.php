<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('leave_type', ['conge', 'permission', 'autre']);
            $table->date('decision_date');
            $table->string('decision_number')->unique(); // DEC-2024-001
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days');
            $table->text('description')->nullable();
            $table->string('decision_document_path'); // PDF signé par DG
            $table->foreignId('signed_by')->nullable()->constrained('users')->onDelete('set null'); // DG
            $table->timestamp('signed_at')->nullable();
            $table->enum('status', ['draft', 'signed', 'used', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_decisions');
    }
};
