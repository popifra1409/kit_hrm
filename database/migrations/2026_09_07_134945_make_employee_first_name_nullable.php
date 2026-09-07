<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // On évite Schema::table()->change() : Laravel/Doctrine réécrit toute la
        // définition de colonne (type inclus), ce que Postgres refuse ici car
        // la colonne générée "full_name" dépend de "first_name".
        // Un ALTER ciblé sur la contrainte NOT NULL uniquement fonctionne.
        DB::statement('ALTER TABLE employees ALTER COLUMN first_name DROP NOT NULL');
    }

    public function down(): void
    {
        // ⚠️ N'annule que si aucun employé n'a first_name à null,
        // sinon échouera volontairement pour éviter de perdre des données.
        DB::statement('ALTER TABLE employees ALTER COLUMN first_name SET NOT NULL');
    }
};
