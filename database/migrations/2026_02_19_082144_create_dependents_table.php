<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Type d'ayant droit
            $table->enum('relationship', [
                'spouse',           // Conjoint(e)
                'child',            // Enfant
                'father',           // Père
                'mother'            // Mère
            ]);

            // Informations personnelles
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->string('birth_place')->nullable();
            $table->enum('gender', ['M', 'F']);

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Documents justificatifs
            $table->string('id_card_path')->nullable(); // CNI
            $table->string('birth_certificate_path')->nullable(); // Acte de naissance
            $table->string('marriage_certificate_path')->nullable(); // Acte de mariage (pour conjoint)
            $table->string('death_certificate_path')->nullable(); // Acte de décès (si déclaration de décès)
            $table->string('photo_path')->nullable();

            // Statut
            $table->boolean('is_alive')->default(true);
            $table->date('death_date')->nullable();
            $table->boolean('is_active')->default(true); // Actif dans le système de prise en charge

            // Prise en charge
            $table->decimal('coverage_rate', 5, 2)->default(75.00); // Taux de prise en charge en %
            $table->date('coverage_start_date')->nullable();
            $table->date('coverage_end_date')->nullable();

            // Carte de prise en charge
            $table->string('card_number')->unique()->nullable();
            $table->boolean('card_issued')->default(false);
            $table->date('card_issue_date')->nullable();
            $table->date('card_expiry_date')->nullable();
            $table->boolean('card_active')->default(false);

            // Observations
            $table->text('medical_notes')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            // Index
            $table->index(['employee_id', 'relationship']);
            $table->index('card_number');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependents');
    }
};
