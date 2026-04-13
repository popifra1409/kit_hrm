<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['medical', 'administrative']);
            $table->text('description')->nullable();

            // Rattachement - MODIFIÉ
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('medical_department_id')->nullable();

            // Responsables - Services médicaux - MODIFIÉ
            $table->unsignedBigInteger('head_of_service_id')->nullable();
            $table->unsignedBigInteger('major_id')->nullable();

            // Responsables - Services administratifs - MODIFIÉ
            $table->unsignedBigInteger('service_chief_id')->nullable();
            $table->unsignedBigInteger('deputy_director_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
