<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_grids', function (Blueprint $table) {
            $table->id();
            $table->integer('category'); // Catégorie (ex: 8)
            $table->integer('echelon'); // Échelon (ex: 3)
            $table->decimal('base_salary', 15, 2); // Salaire de base
            $table->date('effective_date'); // Date d'application
            $table->date('end_date')->nullable(); // Date de fin (si changement)
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['category', 'echelon', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_grids');
    }
};
