<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('payroll_lines', function (Blueprint $table) {
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('payroll_item_id')->references('id')->on('payroll_items')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['validated_by']);
        });

        Schema::table('payroll_lines', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
            $table->dropForeign(['payroll_item_id']);
        });
    }
};
