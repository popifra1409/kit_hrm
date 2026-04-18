<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_directions', function (Blueprint $table) {
            $table->id();

            // Rattachement à une Direction
            $table->foreignId('direction_id')->constrained('directions')->cascadeOnDelete();

            $table->string('name'); // Sous-Direction des Finances
            $table->string('code')->unique(); // SDF
            $table->string('acronym')->nullable();
            $table->text('description')->nullable();

            // Responsable (Sous-Directeur)
            $table->foreignId('sub_director_id')->nullable()->constrained('employees')->nullOnDelete();

            // Hiérarchie
            $table->integer('order')->default(0);

            // Statut
            $table->boolean('is_active')->default(true);

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('location')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_directions');
    }
};
