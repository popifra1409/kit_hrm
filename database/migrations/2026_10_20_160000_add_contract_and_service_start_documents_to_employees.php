<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pièces jointes de la fiche employé déclarées au recensement : le contrat de
 * travail et l'ordre de prise de service. Chemins relatifs du disque « public »,
 * comme employees.photo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'contract_document_path')) {
                $table->string('contract_document_path')->nullable()->after('contract_number');
            }
            if (!Schema::hasColumn('employees', 'service_start_document_path')) {
                $table->string('service_start_document_path')->nullable()->after('service_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['contract_document_path', 'service_start_document_path'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
