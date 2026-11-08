<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le dépôt contenait ici une copie de AdminPanelProvider (classe déclarée deux fois),
     * ce qui faisait échouer toute migration à partir de zéro, donc tous les tests
     * RefreshDatabase. Corps rétabli d'après la colonne réellement présente en base.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
