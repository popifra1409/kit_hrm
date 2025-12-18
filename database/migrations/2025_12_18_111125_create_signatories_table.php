<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Utilisateur lié
            $table->string('full_name'); // Nom complet
            $table->string('position'); // Fonction/Titre
            $table->string('document_type'); // leave_decision, payroll, contract, etc.
            $table->integer('signature_order')->default(1); // Ordre de signature (1, 2, 3)
            $table->string('signature_path')->nullable(); // Chemin de l'image de signature
            $table->string('stamp_path')->nullable(); // Cachet
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['document_type', 'signature_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatories');
    }
};
