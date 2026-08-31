<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_bodies', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('category', ['medical', 'technical', 'administrative', 'support'])->default('administrative');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('trade_bodies', function (Blueprint $table) {
            $table->dropColumn(['name', 'code', 'description', 'category', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};
