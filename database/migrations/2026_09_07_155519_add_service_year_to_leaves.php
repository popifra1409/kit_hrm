<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (!Schema::hasColumn('leaves', 'service_year')) {
                // Le Nème cycle de 12 mois depuis la date de recrutement de l'employé,
                // auquel ce congé est rattaché (calculé à la création de la demande).
                $table->unsignedInteger('service_year')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (Schema::hasColumn('leaves', 'service_year')) {
                $table->dropColumn('service_year');
            }
        });
    }
};
