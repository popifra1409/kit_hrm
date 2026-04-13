<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Raison sociale
            $table->string('registration_number')->unique(); // N° Registre Commerce
            $table->string('tax_number')->nullable(); // N° Contribuable
            $table->string('armp_number')->nullable(); // N° Agrément ARMP
            $table->enum('supplier_type', ['individual', 'company', 'consortium'])->default('company');
            $table->enum('category', ['works', 'goods', 'services', 'consulting']); // Catégorie
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Cameroun');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('specialties')->nullable(); // Spécialités (JSON)
            $table->enum('status', ['active', 'suspended', 'blacklisted'])->default('active');
            $table->decimal('performance_score', 5, 2)->nullable(); // Score de performance
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
