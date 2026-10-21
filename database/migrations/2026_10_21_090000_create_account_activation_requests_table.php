<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_activation_requests', function (Blueprint $table) {
            $table->id();

            // Référence lisible que l'employé montre au guichet (ex. "RH-7K4QF2").
            $table->string('reference', 12)->unique();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('matricule'); // instantané, la fiche employé peut changer entre-temps

            // Mot de passe choisi à l'étape 1, haché, appliqué uniquement si un agent
            // autorise la demande. Le compte reste tant que tel sur son mot de passe temporaire.
            $table->string('requested_password_hash');

            // Ce que l'employé a cru se rappeler ; sert seulement à l'agent qui vérifie.
            $table->date('self_declared_date')->nullable();

            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            // Passé ce délai, la demande n'est plus validable : l'employé doit la relancer.
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_activation_requests');
    }
};
