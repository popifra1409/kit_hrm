<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('trade_body_id')->constrained('trade_bodies')->onDelete('cascade');
            $table->integer('level_rank')->default(1);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropForeignIdFor('trade_body_id');
            $table->dropColumn(['name', 'code', 'description', 'level_rank', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};
