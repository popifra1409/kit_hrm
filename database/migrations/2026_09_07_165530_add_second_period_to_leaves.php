<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (!Schema::hasColumn('leaves', 'is_split')) {
                $table->boolean('is_split')->default(false)->after('total_days');
            }
            if (!Schema::hasColumn('leaves', 'start_date_2')) {
                $table->date('start_date_2')->nullable()->after('is_split');
            }
            if (!Schema::hasColumn('leaves', 'end_date_2')) {
                $table->date('end_date_2')->nullable()->after('start_date_2');
            }
            if (!Schema::hasColumn('leaves', 'total_days_2')) {
                $table->integer('total_days_2')->nullable()->after('end_date_2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            foreach (['is_split', 'start_date_2', 'end_date_2', 'total_days_2'] as $column) {
                if (Schema::hasColumn('leaves', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
