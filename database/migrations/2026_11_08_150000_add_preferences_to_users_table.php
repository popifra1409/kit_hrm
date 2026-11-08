<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réglages suivis par l'agent d'un appareil à l'autre : langue, apparence, police.
     *
     * Nullable volontairement : un compte qui n'a encore rien enregistré garde les valeurs
     * de l'appareil, au lieu de les écraser au premier identifiant.
     *
     * L'adresse du serveur n'y figure pas, et n'y figurera pas : elle dépend du réseau où
     * l'appareil est branché (Wifi de la structure, maison, 4G), pas de la personne. La
     * synchroniser ferait suivre l'adresse du réseau local à un agent connecté ailleurs.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferences');
        });
    }
};
