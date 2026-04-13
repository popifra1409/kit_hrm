<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('restrict');
            $table->foreign('approved_by_n1')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_n2')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['leave_type_id']);
            $table->dropForeign(['approved_by_n1']);
            $table->dropForeign(['approved_by_n2']);
            $table->dropForeign(['rejected_by']);
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['leave_type_id']);
        });
    }
};
