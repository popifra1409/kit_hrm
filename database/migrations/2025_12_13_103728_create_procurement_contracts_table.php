<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('contract_number')->unique();

            // Montants
            $table->decimal('contract_amount', 15, 2);
            $table->decimal('vat_amount', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2);

            // Dates
            $table->date('signature_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_days')->nullable(); // Durée en jours

            // Garanties
            $table->decimal('performance_bond', 15, 2)->nullable(); // Caution de bonne exécution
            $table->decimal('advance_payment', 15, 2)->nullable(); // Avance de démarrage
            $table->integer('warranty_period_months')->nullable(); // Garantie

            // Parties
            $table->text('chuy_representative')->nullable(); // Représentant CHUY
            $table->text('supplier_representative')->nullable(); // Représentant Fournisseur

            // Documents
            $table->string('contract_document_path')->nullable();
            $table->string('signed_contract_path')->nullable();

            // Statut
            $table->enum('status', [
                'draft',              // Brouillon
                'pending_signature',  // En attente signature
                'signed',             // Signé
                'in_execution',       // En exécution
                'completed',          // Terminé
                'suspended',          // Suspendu
                'terminated'          // Résilié
            ])->default('draft');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_contracts');
    }
};
