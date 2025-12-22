<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            // Un employé ne peut accuser réception qu'une fois par document
            $table->unique(['document_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_acknowledgments');
    }
};
