<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La file du Service RH traite désormais deux démarches avec les mêmes règles
     * d'identité : l'activation d'un compte jamais ouvert, et la réinitialisation
     * d'un mot de passe oublié.
     */
    public function up(): void
    {
        Schema::table('account_activation_requests', function (Blueprint $table) {
            $table->string('type', 20)->default('activation')->after('matricule')->index();

            // Une réinitialisation n'a rien à appliquer tel quel : le mot de passe
            // temporaire est généré par l'agent au moment où il valide la demande.
            $table->string('requested_password_hash')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('account_activation_requests', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
