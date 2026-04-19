<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. PÉRIODES DE CALCUL
        Schema::create('quotpart_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // Ex: 2026-04
            $table->integer('year');
            $table->integer('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_revenue', 15, 2)->default(0); // Recette totale du mois
            $table->decimal('quotpart_percentage', 5, 2)->default(0); // % à redistribuer
            $table->decimal('quotpart_amount', 15, 2)->default(0); // Montant total à distribuer
            $table->enum('status', ['draft', 'validated', 'calculated', 'distributed'])->default('draft');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        // 2. PARAMÈTRES DE CALCUL (Système de pondération)
        Schema::create('quotpart_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // 'base', 'performance', 'medical', 'management'
            $table->string('code')->unique(); // Ex: 'indice_weight', 'consultation_weight'
            $table->string('name'); // Nom du paramètre
            $table->text('description')->nullable();
            $table->string('applies_to')->nullable(); // 'all', 'soignant', 'non_soignant', 'management'
            $table->decimal('weight', 8, 4)->default(1); // Coefficient/poids
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // 3. CRITÈRES D'ÉVALUATION (Notes)
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // 'comportement', 'competence', 'performance'
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('max_score', 5, 2)->default(20); // Note maximale
            $table->decimal('weight', 5, 2)->default(1); // Poids dans le calcul
            $table->string('applies_to')->nullable(); // Type de personnel concerné
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // 4. ÉVALUATIONS DES EMPLOYÉS
        Schema::create('employee_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('quotpart_periods')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('evaluation_criteria')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('employees'); // Chef qui note
            $table->decimal('score', 5, 2); // Note obtenue
            $table->text('comment')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_id', 'criterion_id']);
        });

        // 5. ACTIVITÉS MÉDICALES (Pour personnel soignant)
        Schema::create('medical_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('quotpart_periods')->cascadeOnDelete();
            $table->string('activity_type'); // 'consultation', 'prescription', 'acte', 'garde', 'astreinte'
            $table->integer('quantity')->default(0); // Nombre
            $table->decimal('unit_value', 10, 2)->nullable(); // Valeur unitaire si applicable
            $table->decimal('total_value', 10, 2)->nullable(); // Valeur totale
            $table->text('details')->nullable();
            $table->date('activity_date');
            $table->foreignId('validated_by')->nullable()->constrained('employees');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        // 6. DÉCLARATION DES RECETTES
        Schema::create('revenue_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('quotpart_periods')->cascadeOnDelete();
            $table->string('source'); // 'consultations', 'hospitalisations', 'pharmacie', 'imagerie', 'labo'
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('declared_by')->constrained('users');
            $table->timestamp('declared_at');
            $table->timestamps();
        });

        // 7. RETENUES (Impôts, CNPS, etc.)
        Schema::create('quotpart_deduction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'cnps', 'irpp', 'crtv'
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('calculation_type', ['percentage', 'fixed', 'progressive']); // Type de calcul
            $table->decimal('rate', 5, 2)->nullable(); // Taux en %
            $table->decimal('fixed_amount', 10, 2)->nullable(); // Montant fixe
            $table->json('progressive_brackets')->nullable(); // Barème progressif
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // 8. DISTRIBUTIONS DE QUOTE-PARTS (Résultat final)
        Schema::create('quotpart_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('quotpart_periods')->cascadeOnDelete();

            // Éléments de base
            $table->decimal('base_indice_points', 10, 2)->default(0);
            $table->decimal('evaluation_points', 10, 2)->default(0);
            $table->decimal('medical_activity_points', 10, 2)->default(0);
            $table->decimal('management_bonus_points', 10, 2)->default(0);
            $table->decimal('anciennete_points', 10, 2)->default(0);

            // Total points et quote-part brute
            $table->decimal('total_points', 10, 2)->default(0);
            $table->decimal('gross_quotpart', 15, 2)->default(0);

            // Retenues
            $table->decimal('cnps_deduction', 10, 2)->default(0);
            $table->decimal('irpp_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);

            // Net à payer
            $table->decimal('net_quotpart', 15, 2)->default(0);

            // Métadonnées
            $table->json('calculation_details')->nullable(); // Détails du calcul
            $table->text('notes')->nullable();
            $table->enum('status', ['calculated', 'validated', 'paid'])->default('calculated');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_id']);
        });

        // 9. HISTORIQUE DES PAIEMENTS
        Schema::create('quotpart_payment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('quotpart_distributions')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method'); // 'virement', 'especes', 'cheque'
            $table->string('reference')->nullable();
            $table->foreignId('processed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotpart_payment_history');
        Schema::dropIfExists('quotpart_distributions');
        Schema::dropIfExists('quotpart_deduction_types');
        Schema::dropIfExists('revenue_declarations');
        Schema::dropIfExists('medical_activities');
        Schema::dropIfExists('employee_evaluations');
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('quotpart_parameters');
        Schema::dropIfExists('quotpart_periods');
    }
};
