<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'marital_status')) {
                $table->string('marital_status')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('employees', 'children_under_6')) {
                $table->unsignedSmallInteger('children_under_6')->nullable()->after('marital_status');
            }
            if (!Schema::hasColumn('employees', 'total_children')) {
                $table->unsignedSmallInteger('total_children')->nullable()->after('children_under_6');
            }
            if (!Schema::hasColumn('employees', 'id_card_number')) {
                $table->string('id_card_number')->nullable()->after('total_children');
            }
            if (!Schema::hasColumn('employees', 'service_start_date')) {
                $table->date('service_start_date')->nullable()->after('recruitment_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['marital_status', 'children_under_6', 'total_children', 'id_card_number', 'service_start_date'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
