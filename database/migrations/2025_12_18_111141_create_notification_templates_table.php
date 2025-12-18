<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // leave_approved, payroll_ready, etc.
            $table->string('name'); // Nom du template
            $table->string('category'); // leave, payroll, procurement, system
            $table->text('description')->nullable();

            // Canaux de notification
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('system_enabled')->default(true); // Notification interne

            // Contenu email
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();

            // Contenu SMS
            $table->text('sms_body')->nullable();

            // Contenu WhatsApp
            $table->text('whatsapp_body')->nullable();

            // Contenu système
            $table->string('system_title')->nullable();
            $table->text('system_body')->nullable();
            $table->string('system_icon')->nullable(); // heroicon
            $table->string('system_color')->default('info'); // success, info, warning, danger

            // Variables disponibles (JSON)
            $table->json('available_variables')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
