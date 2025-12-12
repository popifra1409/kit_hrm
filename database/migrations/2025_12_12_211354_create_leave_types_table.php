<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Congé annuel, Maladie, Maternité, etc.
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->integer('default_days'); // Nombre de jours par défaut
            $table->integer('max_days_per_year')->nullable(); // Maximum par an
            $table->boolean('requires_document')->default(false); // Nécessite justificatif
            $table->boolean('is_paid')->default(true); // Payé ou non
            $table->boolean('deductible_from_annual')->default(false); // Déduit du congé annuel
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
