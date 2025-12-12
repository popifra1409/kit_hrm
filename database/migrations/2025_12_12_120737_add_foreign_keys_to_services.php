<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('medical_department_id')->references('id')->on('medical_departments')->onDelete('set null');
            $table->foreign('head_of_service_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('major_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('service_chief_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('deputy_director_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['medical_department_id']);
            $table->dropForeign(['head_of_service_id']);
            $table->dropForeign(['major_id']);
            $table->dropForeign(['service_chief_id']);
            $table->dropForeign(['deputy_director_id']);
        });
    }
};
