<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('cnps_number', 13)->nullable()->after('matricule'); // Format: XXXXXXXXXX-X (10+1)
            $table->string('numero_contribuable', 14)->nullable()->after('cnps_number');
            $table->string('matricule_interne', 14)->nullable()->after('numero_contribuable'); // Matricule interne employeur
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['cnps_number', 'numero_contribuable', 'matricule_interne']);
        });
    }
};
