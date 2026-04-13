<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_hierarchies', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('organization_level_id')->references('id')->on('organization_levels')->onDelete('restrict');
            $table->foreign('superior_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('medical_department_id')->references('id')->on('medical_departments')->onDelete('set null');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employee_hierarchies', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['organization_level_id']);
            $table->dropForeign(['superior_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['medical_department_id']);
            $table->dropForeign(['service_id']);
        });
    }
};
