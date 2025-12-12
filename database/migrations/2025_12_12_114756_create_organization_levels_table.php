<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Président CA, DG, DGA, Directeur, Sous-directeur, Chef département, Chef service, Major
            $table->string('code')->unique();
            $table->integer('hierarchy_level'); // 1=Président CA, 2=DG, 3=DGA, etc.
            $table->enum('branch', ['executive', 'medical', 'administrative']); // Branche
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_levels');
    }
};
