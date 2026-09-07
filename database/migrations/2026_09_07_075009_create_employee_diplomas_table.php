<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_diplomas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // recruitment_diploma | highest_diploma | training
            $table->string('type');

            $table->string('title'); // ex: "Licence en Informatique", "Certification ITIL"
            $table->string('institution'); // école/université/organisme
            $table->unsignedSmallInteger('year_obtained');
            $table->string('document_path')->nullable(); // upload pour vérification

            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Un seul "diplôme de recrutement" et un seul "diplôme le plus élevé" par employé
        // (les formations, elles, sont illimitées) — index unique partiel PostgreSQL.
        DB::statement("
            CREATE UNIQUE INDEX employee_diplomas_unique_singleton_type
            ON employee_diplomas (employee_id, type)
            WHERE type IN ('recruitment_diploma', 'highest_diploma') AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_diplomas');
    }
};
