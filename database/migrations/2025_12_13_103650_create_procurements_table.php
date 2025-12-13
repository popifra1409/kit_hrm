<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Ex: CHUY/2025/001
            $table->string('title'); // Objet du marché
            $table->unsignedBigInteger('procurement_type_id');
            $table->text('description')->nullable();

            // Procédure
            $table->enum('procedure', [
                'open_tender',        // Appel d'offres ouvert
                'restricted_tender',  // Appel d'offres restreint
                'consultation',       // Consultation
                'direct_agreement',   // Gré à gré
                'request_for_quote'   // Demande de cotation
            ]);

            // Montants
            $table->decimal('estimated_amount', 15, 2); // Montant estimé
            $table->string('currency')->default('FCFA');
            $table->decimal('reserve_price', 15, 2)->nullable(); // Prix de réserve

            // Service demandeur
            $table->unsignedBigInteger('requesting_department_id')->nullable();
            $table->unsignedBigInteger('requesting_service_id')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable(); // User

            // Dates
            $table->date('publication_date')->nullable();
            $table->date('deadline_questions')->nullable(); // Date limite questions
            $table->date('deadline_submission')->nullable(); // Date limite dépôt offres
            $table->date('opening_date')->nullable(); // Date ouverture des plis

            // Workflow
            $table->enum('status', [
                'draft',              // Brouillon
                'pending_approval',   // En attente d'approbation
                'approved',           // Approuvé
                'published',          // Publié
                'bids_received',      // Offres reçues
                'evaluation',         // En évaluation
                'awarded',            // Attribué
                'contract_signed',    // Contrat signé
                'cancelled',          // Annulé
                'rejected'            // Rejeté
            ])->default('draft');

            // Approbations
            $table->unsignedBigInteger('approved_by_n1')->nullable(); // Chef service
            $table->unsignedBigInteger('approved_by_n2')->nullable(); // DRH/DAF
            $table->unsignedBigInteger('approved_by_n3')->nullable(); // DG
            $table->timestamp('approved_at_n1')->nullable();
            $table->timestamp('approved_at_n2')->nullable();
            $table->timestamp('approved_at_n3')->nullable();

            // ARMP
            $table->boolean('requires_armp')->default(false);
            $table->string('armp_reference')->nullable();
            $table->date('armp_submission_date')->nullable();
            $table->enum('armp_status', ['pending', 'approved', 'rejected', 'not_required'])->default('not_required');

            // Attribution
            $table->unsignedBigInteger('awarded_supplier_id')->nullable();
            $table->decimal('awarded_amount', 15, 2)->nullable();
            $table->date('award_date')->nullable();
            $table->text('award_justification')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};
