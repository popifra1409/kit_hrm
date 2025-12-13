<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de l'élément
            $table->string('code')->unique(); // Code (SALBASE, PRIMEANC, IRPP, etc.)
            $table->enum('type', ['gain', 'deduction']); // Gain ou Retenue
            $table->enum('category', [
                'base',           // Salaire de base
                'prime',          // Primes
                'indemnity',      // Indemnités
                'tax',            // Impôts (IRPP, CAC)
                'social',         // Cotisations sociales (CNPS, Pension)
                'other_deduction' // Autres retenues
            ]);
            $table->boolean('is_taxable')->default(true); // Imposable
            $table->boolean('is_subject_to_cnps')->default(true); // Soumis à CNPS
            $table->enum('calculation_method', [
                'fixed',          // Montant fixe
                'percentage',     // Pourcentage du salaire
                'formula'         // Formule personnalisée
            ])->default('fixed');
            $table->decimal('fixed_amount', 15, 2)->nullable(); // Montant fixe
            $table->decimal('percentage', 5, 2)->nullable(); // Pourcentage
            $table->text('formula')->nullable(); // Formule
            $table->integer('display_order')->default(0); // Ordre d'affichage
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
