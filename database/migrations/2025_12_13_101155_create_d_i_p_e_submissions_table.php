<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dipe_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('numero_dipe', 5); // Numéro DIPE
            $table->string('cle_numero_dipe', 1); // Clé
            $table->string('numero_contribuable', 14); // N° contribuable CHUY
            $table->integer('month'); // Mois (1-12)
            $table->integer('year'); // Année
            $table->enum('type', ['mensuel', 'debut_exercice', 'fin_exercice'])->default('mensuel');
            $table->enum('regime_cnps', ['1', '2'])->default('1'); // Régime 1 = général

            // Statistiques
            $table->integer('total_employees')->default(0);
            $table->decimal('total_salaire_brut', 15, 2)->default(0);
            $table->decimal('total_salaire_cotisable', 15, 2)->default(0);
            $table->decimal('total_cotisations_cnps', 15, 2)->default(0);
            $table->decimal('total_irpp', 15, 2)->default(0);

            // Fichiers générés
            $table->string('file_path')->nullable(); // Fichier TXT généré
            $table->string('excel_path')->nullable(); // Fichier Excel (optionnel)

            $table->enum('status', ['draft', 'validated', 'submitted', 'accepted', 'rejected'])->default('draft');
            $table->date('submission_date')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['month', 'year', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dipe_submissions');
    }
};
