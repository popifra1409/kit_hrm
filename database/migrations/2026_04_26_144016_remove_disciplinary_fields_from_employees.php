<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'disciplinary_points')) {
                $table->dropColumn('disciplinary_points');
            }
            if (Schema::hasColumn('employees', 'disciplinary_notes')) {
                $table->dropColumn('disciplinary_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('disciplinary_points', 5, 2)->default(0);
            $table->text('disciplinary_notes')->nullable();
        });
    }
};
