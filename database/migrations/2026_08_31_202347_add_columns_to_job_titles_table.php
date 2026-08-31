<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('level', [
                'president',
                'director_general',
                'director_general_adjoint',
                'director',
                'chief_department',
                'chief_service',
                'major',
                'chief_unit',
                'employee'
            ])->unique();
            $table->integer('hierarchy_level')->default(0);
            $table->boolean('is_managerial')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->dropColumn(['name', 'code', 'description', 'level', 'hierarchy_level', 'is_managerial', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};
