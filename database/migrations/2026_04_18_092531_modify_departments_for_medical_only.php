<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Ajouter type pour distinguer (si pas déjà présent)
            if (!Schema::hasColumn('departments', 'type')) {
                $table->enum('type', ['medical', 'surgical', 'diagnostic', 'support'])->default('medical')->after('name');
            }

            // Chef de département (équivalent Sous-Directeur niveau médical)
            if (!Schema::hasColumn('departments', 'department_head_id')) {
                $table->foreignId('department_head_id')->nullable()->constrained('employees')->nullOnDelete()->after('description');
            }

            // Niveau hiérarchique (équivalent sous-direction)
            if (!Schema::hasColumn('departments', 'hierarchical_level')) {
                $table->string('hierarchical_level')->default('sub_direction')->after('type');
            }

            // Ordre et contact si pas présent
            if (!Schema::hasColumn('departments', 'order')) {
                $table->integer('order')->default(0)->after('hierarchical_level');
            }

            if (!Schema::hasColumn('departments', 'phone')) {
                $table->string('phone')->nullable()->after('order');
                $table->string('email')->nullable()->after('phone');
                $table->text('location')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'department_head_id',
                'hierarchical_level',
                'order',
                'phone',
                'email',
                'location'
            ]);
        });
    }
};
