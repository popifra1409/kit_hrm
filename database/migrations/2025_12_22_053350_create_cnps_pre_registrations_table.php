<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cnps_pre_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Informations employeur
            $table->string('employer_name')->default('Centre Hospitalier Universitaire de Yaoundé');
            $table->string('employer_cnps_number')->nullable(); // N° CNPS de l'employeur
            $table->string('employer_address')->nullable();
            $table->string('employer_phone')->nullable();

            // Informations personnelles de l'employé
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->string('birth_place');
            $table->enum('gender', ['M', 'F']);
            $table->string('nationality')->default('Camerounaise');

            // Pièce d'identité
            $table->enum('id_type', ['cni', 'passport', 'residence_permit'])->default('cni');
            $table->string('id_number');
            $table->date('id_issue_date')->nullable();
            $table->date('id_expiry_date')->nullable();
            $table->string('id_document_path')->nullable(); // Scan de la CNI

            // Adresse
            $table->string('address');
            $table->string('city');
            $table->string('region');
            $table->string('phone');
            $table->string('email')->nullable();

            // Situation familiale
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single');
            $table->integer('number_of_children')->default(0);

            // Informations professionnelles
            $table->string('position_title');
            $table->date('hire_date');
            $table->decimal('monthly_salary', 10, 2);
            $table->enum('contract_type', ['permanent', 'fixed_term', 'temporary'])->default('permanent');

            // Catégorie professionnelle CNPS
            $table->enum('cnps_category', [
                'cadre_superieur',      // Cadre Supérieur
                'cadre_moyen',          // Cadre Moyen
                'agent_maitrise',       // Agent de Maîtrise
                'employe_qualifie',     // Employé Qualifié
                'employe',              // Employé
                'manoeuvre'             // Manoeuvre
            ])->default('employe');

            // Personne à contacter en cas d'urgence
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Bénéficiaires (pour pension)
            $table->json('beneficiaries')->nullable(); // Array de bénéficiaires

            // Documents requis
            $table->string('birth_certificate_path')->nullable();
            $table->string('marriage_certificate_path')->nullable();
            $table->string('children_birth_certificates_path')->nullable();
            $table->string('medical_certificate_path')->nullable();
            $table->string('photo_path')->nullable();

            // Numéro CNPS (une fois attribué)
            $table->string('cnps_number')->unique()->nullable();
            $table->date('cnps_registration_date')->nullable();

            // Statut de la demande
            $table->enum('status', [
                'draft',           // Brouillon
                'pending',         // En attente de soumission
                'submitted',       // Soumise à la CNPS
                'approved',        // Approuvée par la CNPS
                'rejected',        // Rejetée
                'completed'        // Immatriculation terminée
            ])->default('draft');

            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            // Documents générés
            $table->string('registration_form_path')->nullable(); // Formulaire de pré-immatriculation généré
            $table->string('declaration_form_path')->nullable(); // Déclaration d'embauche

            // Suivi
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index('status');
            $table->index('cnps_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cnps_pre_registrations');
    }
};
