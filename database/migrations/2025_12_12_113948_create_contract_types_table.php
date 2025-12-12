<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // CDI, CDD, Temporaire, Vacataire, Stage
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('requires_end_date')->default(false); // true pour CDD, Temporaire
            $table->integer('max_duration_months')->nullable(); // Durée max en mois
            $table->boolean('renewable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_types');
    }
};
