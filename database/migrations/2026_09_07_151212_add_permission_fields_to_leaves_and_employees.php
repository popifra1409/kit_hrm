<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'matricule_fonction_publique')) {
                $table->string('matricule_fonction_publique')->nullable()->after('matricule');
            }
        });

        Schema::table('leaves', function (Blueprint $table) {
            if (!Schema::hasColumn('leaves', 'destination')) {
                $table->string('destination')->nullable(); // pour Permission d'Absence
            }
            if (!Schema::hasColumn('leaves', 'address_during_leave')) {
                $table->string('address_during_leave')->nullable(); // pour Congé
            }
            if (!Schema::hasColumn('leaves', 'children_under_6_at_request')) {
                $table->unsignedTinyInteger('children_under_6_at_request')->nullable();
            }
            if (!Schema::hasColumn('leaves', 'replacement_id')) {
                $table->foreignId('replacement_id')->nullable()
                    ->constrained('replacements')->nullOnDelete();
            }
            if (!Schema::hasColumn('leaves', 'deductible_from_annual')) {
                // Devient true automatiquement pour les Permissions au-delà du seuil de 10j cumulés
                $table->boolean('deductible_from_annual')->default(false);
            }
            if (!Schema::hasColumn('leaves', 'current_approval_step_id')) {
                $table->unsignedBigInteger('current_approval_step_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'matricule_fonction_publique')) {
                $table->dropColumn('matricule_fonction_publique');
            }
        });

        Schema::table('leaves', function (Blueprint $table) {
            foreach (['destination', 'address_during_leave', 'children_under_6_at_request', 'deductible_from_annual', 'current_approval_step_id'] as $column) {
                if (Schema::hasColumn('leaves', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('leaves', 'replacement_id')) {
                $table->dropConstrainedForeignId('replacement_id');
            }
        });
    }
};
