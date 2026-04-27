<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_types', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_types', 'requires_end_date')) {
                $table->boolean('requires_end_date')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('contract_types', 'max_duration_months')) {
                $table->integer('max_duration_months')->nullable()->after('requires_end_date');
            }
            if (!Schema::hasColumn('contract_types', 'renewable')) {
                $table->boolean('renewable')->default(false)->after('max_duration_months');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_types', function (Blueprint $table) {
            $table->dropColumn(['requires_end_date', 'max_duration_months', 'renewable']);
        });
    }
};
