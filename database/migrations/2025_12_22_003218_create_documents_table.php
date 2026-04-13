<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Informations de base
            $table->string('title');
            $table->string('reference_number')->unique()->nullable();
            $table->unsignedBigInteger('category_id');

            // Type de document
            $table->enum('type', [
                'statute',           // Statut
                'regulation',        // Règlement intérieur
                'policy',            // Politique/Procédure
                'memo',              // Note de service
                'circular',          // Circulaire
                'announcement',      // Communiqué
                'contract_template', // Modèle de contrat
                'form',              // Formulaire
                'report',            // Rapport
                'other'              // Autre
            ]);

            $table->text('description')->nullable();
            $table->text('summary')->nullable();

            // Fichier
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // pdf, docx, xlsx, etc.
            $table->integer('file_size')->default(0); // en bytes

            // Versioning
            $table->string('version')->default('1.0');
            $table->unsignedBigInteger('previous_version_id')->nullable();
            $table->boolean('is_latest_version')->default(true);

            // Dates importantes
            $table->date('issue_date')->nullable(); // Date d'émission
            $table->date('effective_date')->nullable(); // Date d'entrée en vigueur
            $table->date('expiry_date')->nullable(); // Date d'expiration

            // Visibilité et accès
            $table->enum('visibility', [
                'public',     // Tous les employés
                'restricted', // Certains rôles
                'confidential' // Accès restreint
            ])->default('public');

            $table->json('allowed_roles')->nullable(); // Si restricted
            $table->json('allowed_departments')->nullable(); // Si restricted

            // Validation et signature
            $table->boolean('requires_acknowledgment')->default(false); // Nécessite accusé de lecture
            $table->unsignedBigInteger('signed_by')->nullable(); // Signataire
            $table->date('signed_date')->nullable();

            // Statut
            $table->enum('status', [
                'draft',      // Brouillon
                'review',     // En révision
                'approved',   // Approuvé
                'published',  // Publié
                'archived'    // Archivé
            ])->default('draft');

            // Métadonnées
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);

            // Tags
            $table->json('tags')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('document_categories')->onDelete('restrict');
            $table->foreign('previous_version_id')->references('id')->on('documents')->onDelete('set null');
            $table->foreign('signed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index('category_id');
            $table->index('type');
            $table->index('status');
            $table->index('visibility');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
