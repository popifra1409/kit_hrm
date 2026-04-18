<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'is_managerial')) {
                $table->boolean('is_managerial')
                    ->default(false)
                    ->after('level_rank')
                    ->comment('Indique si le poste est un poste de management');
            }
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('is_managerial');
        });
    }
};
