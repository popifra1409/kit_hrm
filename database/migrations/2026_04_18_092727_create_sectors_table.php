<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();

            // Rattachement à un Service
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            $table->string('name'); // Secteur A, Secteur Bloc, etc.
            $table->string('code')->nullable();
            $table->text('description')->nullable();

            // Type de secteur
            $table->enum('type', ['care_unit', 'operational', 'support'])->default('operational');

            // Responsable (Chef Secteur ou Major)
            $table->foreignId('sector_head_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('head_title', ['chef_secteur', 'major', 'responsable'])->default('chef_secteur');

            // Capacité (pour secteurs médicaux)
            $table->integer('bed_capacity')->nullable(); // Nombre de lits

            // Hiérarchie
            $table->integer('order')->default(0);

            // Statut
            $table->boolean('is_active')->default(true);

            // Contact
            $table->string('phone')->nullable();
            $table->text('location')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};
    