<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_affectations') && !Schema::hasColumn('employee_affectations', 'qualification_id')) {
            Schema::table('employee_affectations', function (Blueprint $table) {
                $table->foreignId('qualification_id')->nullable()->constrained('qualifications')->nullOnDelete();
            });
        }

        if (Schema::hasTable('replacements') && !Schema::hasColumn('replacements', 'temporary_qualification_id')) {
            Schema::table('replacements', function (Blueprint $table) {
                $table->foreignId('temporary_qualification_id')->nullable()->constrained('qualifications')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_affectations', 'qualification_id')) {
            Schema::table('employee_affectations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('qualification_id');
            });
        }

        if (Schema::hasColumn('replacements', 'temporary_qualification_id')) {
            Schema::table('replacements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('temporary_qualification_id');
            });
        }
    }
};
