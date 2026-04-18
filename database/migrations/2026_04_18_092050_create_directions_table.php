<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('acronym')->nullable();
            $table->text('description')->nullable();

            // Responsable (Directeur)
            $table->foreignId('director_id')->nullable()->constrained('employees')->nullOnDelete();

            // Type (pour extension future)
            $table->enum('type', ['administrative', 'technique', 'support'])->default('administrative');

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
        Schema::dropIfExists('directions');
    }
};
