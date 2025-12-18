<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Ex: hospital_name, hospital_address
            $table->string('group')->default('general'); // general, hospital, notifications, etc.
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, number, boolean, json, file
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Accessible sans auth
            $table->timestamps();

            $table->index(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
