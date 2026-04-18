<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Changer de integer à decimal(5,2)
            // 5,2 = 5 chiffres total dont 2 après la virgule
            // Ex: 999.99
            $table->decimal('disciplinary_points', 5, 2)
                ->default(0)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('disciplinary_points')
                ->default(0)
                ->change();
        });
    }
};
