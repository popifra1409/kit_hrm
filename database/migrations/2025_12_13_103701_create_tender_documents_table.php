<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->string('name'); // Nom du document
            $table->enum('type', [
                'dao',              // Dossier d'Appel d'Offres
                'technical_specs',  // Spécifications techniques
                'contract_model',   // Modèle de contrat
                'terms',            // Cahier des charges
                'plan',             // Plan/Dessin
                'other'             // Autre
            ]);
            $table->string('file_path'); // Chemin du fichier
            $table->string('file_name'); // Nom original
            $table->integer('file_size')->nullable(); // Taille en octets
            $table->string('mime_type')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_mandatory')->default(false); // Obligatoire dans le dépôt
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_documents');
    }
};
