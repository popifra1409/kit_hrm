<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'coverage_rate')) {
                // Taux de base de l'employé actif (par défaut 75%). Le taux EFFECTIF
                // (calculé, pas stocké) passe automatiquement à 50% une fois retraité —
                // voir Employee::getEffectiveCoverageRateAttribute().
                $table->decimal('coverage_rate', 5, 2)->default(75.00)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'coverage_rate')) {
                $table->dropColumn('coverage_rate');
            }
        });
    }
};
