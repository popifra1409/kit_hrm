<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Type de carte
            $table->enum('card_type', [
                'professional',     // Carte professionnelle
                'health_coverage'   // Carte de prise en charge
            ]);

            // Informations carte
            $table->string('card_number')->unique();
            $table->string('qr_code_path')->nullable(); // Chemin vers l'image QR code
            $table->string('qr_code_data')->nullable(); // Données encodées dans le QR

            // Validité
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->boolean('is_active')->default(false);

            // Statut
            $table->enum('status', [
                'pending',      // En attente de validation
                'issued',       // Émise
                'active',       // Active
                'suspended',    // Suspendue
                'expired',      // Expirée
                'revoked'       // Révoquée
            ])->default('pending');

            // Activation
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamp('activated_at')->nullable();

            // Révocation
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();

            // Fichier PDF généré
            $table->string('card_pdf_path')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('activated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('revoked_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index(['employee_id', 'card_type']);
            $table->index('card_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_cards');
    }
};
