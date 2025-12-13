<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('reference')->unique(); // Référence de l'offre

            // Montants
            $table->decimal('bid_amount', 15, 2); // Montant de l'offre
            $table->decimal('bid_amount_ht', 15, 2)->nullable(); // HT
            $table->decimal('bid_amount_ttc', 15, 2)->nullable(); // TTC
            $table->decimal('vat_amount', 15, 2)->nullable(); // TVA

            // Délais
            $table->integer('execution_period')->nullable(); // Délai d'exécution (jours)
            $table->integer('warranty_period')->nullable(); // Garantie (mois)

            // Soumission
            $table->timestamp('submitted_at')->nullable();
            $table->string('submitted_by')->nullable(); // Nom du représentant
            $table->boolean('is_late')->default(false); // Hors délai

            // Conformité technique
            $table->boolean('is_technically_compliant')->nullable();
            $table->text('technical_compliance_notes')->nullable();

            // Conformité financière
            $table->boolean('is_financially_compliant')->nullable();
            $table->text('financial_compliance_notes')->nullable();

            // Documents
            $table->boolean('has_required_documents')->nullable();
            $table->text('missing_documents')->nullable();

            // Statut
            $table->enum('status', [
                'submitted',      // Soumise
                'under_review',   // En examen
                'compliant',      // Conforme
                'non_compliant',  // Non conforme
                'shortlisted',    // Présélectionnée
                'rejected',       // Rejetée
                'awarded',        // Retenue
                'not_awarded'     // Non retenue
            ])->default('submitted');

            $table->decimal('total_score', 5, 2)->nullable(); // Score total
            $table->integer('rank')->nullable(); // Classement

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
