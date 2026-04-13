<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->storedAs("last_name || ' ' || first_name");

            // Catégorie et échelon
            $table->string('category_recruitment')->nullable(); // Ex: 7/1
            $table->string('category_current')->nullable(); // Ex: 7/9
            $table->integer('category_number')->nullable(); // 7
            $table->integer('echelon_number')->nullable(); // 9

            // Informations professionnelles
            $table->string('qualification'); // Poste
            //$table->foreignId('department_id')->nullable()->constrained();
            //$table->foreignId('position_id')->nullable()->constrained();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->enum('employment_type', ['permanent', 'contract', 'temporary'])->default('permanent');
            $table->enum('personnel_type', ['soignant', 'non_soignant'])->default('non_soignant');

            // Dates importantes
            $table->date('birth_date');
            $table->date('recruitment_date');
            $table->date('service_start_date');
            $table->date('retirement_date')->nullable(); // Calculée automatiquement
            $table->integer('retirement_age')->default(60); // Âge de départ à la retraite

            // Informations bancaires et contrat
            $table->string('contract_number')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();

            // Informations de contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // Informations familiales (pour calcul congés)
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single');
            $table->integer('children_under_6')->default(0);
            $table->integer('total_children')->default(0);

            // Dossier disciplinaire
            $table->integer('disciplinary_points')->default(0); // 0 = bon, >0 = sanctions
            $table->text('disciplinary_notes')->nullable();

            // Statut
            $table->enum('status', ['active', 'on_leave', 'retired', 'suspended', 'terminated'])->default('active');
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
