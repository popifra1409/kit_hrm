<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // 1. Ajouter les contraintes de clés étrangères manquantes
            if (!Schema::hasColumn('contracts', 'employee_id')) {
                // La colonne existe déjà mais sans contrainte
                $table->foreign('employee_id')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('contracts', 'contract_type_id')) {
                $table->foreign('contract_type_id')
                    ->references('id')
                    ->on('contract_types');
            }

            // 2. Renommer base_salary en salary
            if (Schema::hasColumn('contracts', 'base_salary') && !Schema::hasColumn('contracts', 'salary')) {
                $table->renameColumn('base_salary', 'salary');
            }

            // 3. Ajouter les nouvelles colonnes
            if (!Schema::hasColumn('contracts', 'signature_date')) {
                $table->date('signature_date')->nullable()->after('end_date');
            }

            if (!Schema::hasColumn('contracts', 'position')) {
                $table->string('position')->nullable()->after('salary');
            }

            if (!Schema::hasColumn('contracts', 'work_location')) {
                $table->text('work_location')->nullable()->after('position');
            }

            if (!Schema::hasColumn('contracts', 'document_path')) {
                $table->string('document_path')->nullable()->after('work_location');
            }

            if (!Schema::hasColumn('contracts', 'renewal_count')) {
                $table->integer('renewal_count')->default(0)->after('document_path');
            }

            if (!Schema::hasColumn('contracts', 'renewed_from_id')) {
                $table->foreignId('renewed_from_id')
                    ->nullable()
                    ->after('renewal_count')
                    ->constrained('contracts');
            }

            if (!Schema::hasColumn('contracts', 'notes')) {
                $table->text('notes')->nullable()->after('termination_reason');
            }

            if (!Schema::hasColumn('contracts', 'validated_by')) {
                $table->foreignId('validated_by')
                    ->nullable()
                    ->after('notes')
                    ->constrained('users');
            }

            if (!Schema::hasColumn('contracts', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }

            if (!Schema::hasColumn('contracts', 'deleted_at')) {
                $table->softDeletes();
            }

            // 4. Garder terms et is_current pour compatibilité (optionnel)
            // Ils existent déjà, donc on ne fait rien
        });

        echo "✅ Table contracts mise à jour pour la gestion RH\n";
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Supprimer les contraintes
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['contract_type_id']);
            $table->dropForeign(['renewed_from_id']);
            $table->dropForeign(['validated_by']);

            // Renommer salary en base_salary
            if (Schema::hasColumn('contracts', 'salary')) {
                $table->renameColumn('salary', 'base_salary');
            }

            // Supprimer les nouvelles colonnes
            $columns = [
                'signature_date',
                'position',
                'work_location',
                'document_path',
                'renewal_count',
                'renewed_from_id',
                'notes',
                'validated_by',
                'validated_at',
                'deleted_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
