<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            ///$table->foreignId('current_service_id')->nullable()->after('position_id')->constrained('services');
            //$table->foreignId('contract_type_id')->nullable()->after('employment_type')->constrained('contract_types');
            $table->unsignedBigInteger('current_service_id')->nullable()->after('position_id');
            $table->unsignedBigInteger('contract_type_id')->nullable()->after('employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['current_service_id']);
            $table->dropForeign(['contract_type_id']);
            $table->dropColumn(['current_service_id', 'contract_type_id']);
        });
    }
};
