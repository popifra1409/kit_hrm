<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amendments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('amendment_number'); // Ex: Avenant N°1
            $table->enum('type', [
                'amount',         // Modification montant
                'duration',       // Prolongation délai
                'scope',          // Modification objet
                'terms',          // Modification clauses
                'other'           // Autre
            ]);

            // Modifications financières
            $table->decimal('previous_amount', 15, 2)->nullable();
            $table->decimal('new_amount', 15, 2)->nullable();
            $table->decimal('variation_amount', 15, 2)->nullable();
            $table->decimal('variation_percentage', 5, 2)->nullable();

            // Modifications temporelles
            $table->date('previous_end_date')->nullable();
            $table->date('new_end_date')->nullable();
            $table->integer('extension_days')->nullable();

            $table->text('justification'); // Justification de l'avenant
            $table->text('modifications')->nullable(); // Détail des modifications

            $table->date('signature_date')->nullable();
            $table->string('document_path')->nullable();

            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'signed',
                'rejected'
            ])->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amendments');
    }
};
