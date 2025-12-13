<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: Travaux, Fournitures, Services
            $table->string('code')->unique(); // Ex: TRV, FRN, SVC
            $table->enum('category', ['works', 'goods', 'services', 'consulting']);
            $table->text('description')->nullable();
            $table->decimal('threshold_aon', 15, 2)->nullable(); // Seuil Appel d'Offres National
            $table->decimal('threshold_aoi', 15, 2)->nullable(); // Seuil Appel d'Offres International
            $table->boolean('requires_armp_approval')->default(false);
            $table->integer('min_publication_days')->default(30); // Délai minimum de publication
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_types');
    }
};
