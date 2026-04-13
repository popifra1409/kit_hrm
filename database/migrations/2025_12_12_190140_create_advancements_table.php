<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advancements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('previous_category')->nullable();
            $table->string('new_category');
            $table->integer('previous_echelon')->nullable();
            $table->integer('new_echelon');
            $table->date('advancement_date');
            $table->enum('type', ['automatic', 'exceptional', 'manual'])->default('automatic');
            $table->text('reason')->nullable();
            $table->string('decision_number')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advancements');
    }
};
