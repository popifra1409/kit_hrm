<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->date('report_date');

            // Avancement
            $table->decimal('progress_percentage', 5, 2)->default(0); // % d'avancement
            $table->decimal('amount_executed', 15, 2)->default(0); // Montant exécuté
            $table->decimal('amount_paid', 15, 2)->default(0); // Montant payé

            // Délais
            $table->boolean('is_on_schedule')->default(true);
            $table->integer('delay_days')->default(0); // Jours de retard

            // Qualité
            $table->enum('quality_rating', [
                'excellent',
                'good',
                'satisfactory',
                'unsatisfactory',
                'poor'
            ])->nullable();

            // Problèmes
            $table->boolean('has_issues')->default(false);
            $table->text('issues_description')->nullable();
            $table->text('corrective_actions')->nullable();

            // Évaluation
            $table->text('observations')->nullable();
            $table->text('recommendations')->nullable();

            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_executions');
    }
};
