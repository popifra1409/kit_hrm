<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('census_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('census_submissions', 'canceled_by')) {
                $table->foreignId('canceled_by')->nullable()->after('rejected_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('census_submissions', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('canceled_by');
            }
            if (!Schema::hasColumn('census_submissions', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('canceled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('census_submissions', function (Blueprint $table) {
            foreach (['cancellation_reason', 'canceled_at', 'canceled_by'] as $column) {
                if (Schema::hasColumn('census_submissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
