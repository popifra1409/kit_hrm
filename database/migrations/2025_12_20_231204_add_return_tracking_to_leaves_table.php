<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Colonnes pour le suivi des retours
            $table->date('actual_return_date')->nullable()->after('end_date');
            $table->boolean('has_returned')->default(false)->after('actual_return_date');
            $table->date('return_confirmed_at')->nullable()->after('has_returned');
            $table->unsignedBigInteger('return_confirmed_by')->nullable()->after('return_confirmed_at');
            $table->text('return_notes')->nullable()->after('return_confirmed_by');

            // Alerte si retard
            $table->boolean('is_late_return')->default(false)->after('return_notes');
            $table->integer('late_days')->default(0)->after('is_late_return');

            $table->foreign('return_confirmed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['return_confirmed_by']);
            $table->dropColumn([
                'actual_return_date',
                'has_returned',
                'return_confirmed_at',
                'return_confirmed_by',
                'return_notes',
                'is_late_return',
                'late_days',
            ]);
        });
    }
};
