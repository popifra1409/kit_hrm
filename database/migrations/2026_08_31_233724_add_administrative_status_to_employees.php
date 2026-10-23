<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'administrative_status')) {
                $table->string('administrative_status')->nullable()->after('personnel_type');
            }
        });

        // Best-effort : pré-remplir à partir de l'ancien employment_type pour ne pas repartir de zéro.
        // À ajuster manuellement ensuite (la correspondance n'est qu'indicative).
        if (Schema::hasColumn('employees', 'employment_type')) {
            DB::table('employees')->where('employment_type', 'permanent')
                ->whereNull('administrative_status')
                ->update(['administrative_status' => 'fonctionnaire_affecte']);

            DB::table('employees')->whereIn('employment_type', ['contract', 'temporary'])
                ->whereNull('administrative_status')
                ->update(['administrative_status' => 'contractuel_structure']);

            DB::table('employees')->where('employment_type', 'intern')
                ->whereNull('administrative_status')
                ->update(['administrative_status' => 'stagiaire']);
        }

        // ⚠️ employment_type n'est PAS supprimée ici par sécurité.
        // Une fois que vous avez vérifié/corrigé administrative_status pour tous vos employés,
        // on pourra faire une migration dédiée : dropColumn('employment_type').
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'administrative_status')) {
                $table->dropColumn('administrative_status');
            }
        });
    }
};
