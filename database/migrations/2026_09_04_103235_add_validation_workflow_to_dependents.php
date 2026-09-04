<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dependents', function (Blueprint $table) {
            if (!Schema::hasColumn('dependents', 'validation_status')) {
                $table->string('validation_status')->default('pending')->after('employee_id');
                // valeurs attendues : pending | validated | rejected
            }
            if (!Schema::hasColumn('dependents', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('validation_status')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('dependents', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
            if (!Schema::hasColumn('dependents', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('validated_at');
            }
            if (!Schema::hasColumn('dependents', 'submitted_via')) {
                $table->string('submitted_via')->default('admin')->after('rejection_reason');
                // valeurs attendues : admin | mobile
            }
        });
    }

    public function down(): void
    {
        Schema::table('dependents', function (Blueprint $table) {
            foreach (['validation_status', 'validated_by', 'validated_at', 'rejection_reason', 'submitted_via'] as $column) {
                if (Schema::hasColumn('dependents', $column)) {
                    if ($column === 'validated_by') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
