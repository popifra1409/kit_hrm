<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'trade_body_id')) {
                $table->foreignId('trade_body_id')->nullable()->constrained('trade_bodies')->nullOnDelete();
            }

            if (!Schema::hasColumn('employees', 'qualification_id')) {
                $table->foreignId('qualification_id')->nullable()->constrained('qualifications')->nullOnDelete();
            }

            if (!Schema::hasColumn('employees', 'job_title_id')) {
                $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['trade_body_id', 'qualification_id', 'job_title_id'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};
