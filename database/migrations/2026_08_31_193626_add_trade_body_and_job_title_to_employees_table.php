<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('trade_body_id')->nullable()->constrained('trade_bodies')->onDelete('set null')->after('classification_type');
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->onDelete('set null')->after('trade_body_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Supprimer simplement les colonnes (les FK seront supprimées automatiquement)
            if (Schema::hasColumn('employees', 'trade_body_id')) {
                $table->dropColumn('trade_body_id');
            }
            if (Schema::hasColumn('employees', 'job_title_id')) {
                $table->dropColumn('job_title_id');
            }
        });
    }
};
