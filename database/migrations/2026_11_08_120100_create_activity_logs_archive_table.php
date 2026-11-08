<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entrepôt des journaux versés : mêmes colonnes que activity_logs, plus la période
     * de versement. Il n'y a jamais de clé étrangère ici : une archive doit pouvoir
     * survivre à la suppression des comptes qu'elle décrit.
     */
    public function up(): void
    {
        Schema::create('activity_logs_archive', function (Blueprint $table) {
            $table->id();

            $table->timestamp('occurred_at')->index();
            // '2026-09' : la période de versement, servie par activity-logs:archive.
            $table->string('period', 7)->index();
            $table->timestamp('archived_at');

            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_matricule')->nullable();
            $table->string('actor_name')->nullable();

            $table->string('context', 20)->index();
            $table->string('action', 60)->index();
            $table->string('status', 20)->index();

            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();

            $table->text('reason')->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->index(['occurred_at', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs_archive');
    }
};
