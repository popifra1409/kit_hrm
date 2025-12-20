<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('current_echelon')->nullable()->after('category');
            $table->date('echelon_start_date')->nullable()->after('current_echelon');
            $table->date('last_advancement_date')->nullable()->after('echelon_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['current_echelon', 'echelon_start_date', 'last_advancement_date']);
        });
    }
};
