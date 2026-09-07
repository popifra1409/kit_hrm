<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_diplomas', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_diplomas', 'validation_status')) {
                $table->string('validation_status')->default('pending')->after('type');
                // pending | validated | rejected
            }
            if (!Schema::hasColumn('employee_diplomas', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('employee_diplomas', 'submitted_via')) {
                $table->string('submitted_via')->default('admin')->after('rejection_reason');
                // admin | mobile
            }
        });

        // Aligner les données existantes : is_verified=true -> validated, sinon pending
        DB::table('employee_diplomas')->where('is_verified', true)->update(['validation_status' => 'validated']);
    }

    public function down(): void
    {
        Schema::table('employee_diplomas', function (Blueprint $table) {
            foreach (['validation_status', 'rejection_reason', 'submitted_via'] as $column) {
                if (Schema::hasColumn('employee_diplomas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
