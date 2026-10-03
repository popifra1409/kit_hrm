<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Élargit la colonne status (initialement limitée à submitted/validated/rejected)
        // pour accueillir les nouveaux statuts intermédiaires. Fonctionne que la colonne
        // soit déjà un varchar ou un vrai type ENUM Postgres.
        DB::statement('ALTER TABLE census_submissions ALTER COLUMN status TYPE VARCHAR(30)');

        Schema::table('census_submissions', function (Blueprint $table) {
            $table->foreignId('career_validated_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('career_validated_at')->nullable()->after('career_validated_by');
            $table->text('career_rejection_reason')->nullable()->after('career_validated_at');

            $table->foreignId('solde_validated_by')->nullable()->after('career_rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('solde_validated_at')->nullable()->after('solde_validated_by');
            $table->text('solde_rejection_reason')->nullable()->after('solde_validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('census_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'career_validated_by',
                'career_validated_at',
                'career_rejection_reason',
                'solde_validated_by',
                'solde_validated_at',
                'solde_rejection_reason',
            ]);
        });
    }
};
