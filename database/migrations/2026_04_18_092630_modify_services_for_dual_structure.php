<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Rendre department_id nullable
            $table->foreignId('department_id')->nullable()->change();

            // Ajouter rattachement à sous-direction (administratif)
            if (!Schema::hasColumn('services', 'sub_direction_id')) {
                $table->foreignId('sub_direction_id')->nullable()->constrained('sub_directions')->nullOnDelete()->after('department_id');
            }

            // Type de service
            if (!Schema::hasColumn('services', 'type')) {
                $table->enum('type', ['medical', 'administrative', 'support', 'technical'])->default('medical')->after('name');
            }

            // Chef de service
            if (!Schema::hasColumn('services', 'service_head_id')) {
                $table->foreignId('service_head_id')->nullable()->constrained('employees')->nullOnDelete()->after('description');
            }

            // Ordre et contact
            if (!Schema::hasColumn('services', 'order')) {
                $table->integer('order')->default(0)->after('service_head_id');
            }

            if (!Schema::hasColumn('services', 'phone')) {
                $table->string('phone')->nullable()->after('order');
                $table->string('email')->nullable()->after('phone');
                $table->text('location')->nullable()->after('email');
            }
        });

        // Contrainte : un service doit appartenir SOIT à un department SOIT à une sub_direction
        DB::statement('ALTER TABLE services ADD CONSTRAINT check_parent CHECK (
            (department_id IS NOT NULL AND sub_direction_id IS NULL) OR 
            (department_id IS NULL AND sub_direction_id IS NOT NULL)
        )');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS check_parent');

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['sub_direction_id']);
            $table->dropColumn([
                'sub_direction_id',
                'type',
                'service_head_id',
                'order',
                'phone',
                'email',
                'location'
            ]);
        });
    }
};
