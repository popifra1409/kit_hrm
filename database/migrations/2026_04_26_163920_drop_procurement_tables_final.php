<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        echo "🗑️ Suppression des tables procurement...\n";

        Schema::dropIfExists('procurement_executions');
        Schema::dropIfExists('procurement_contracts');
        Schema::dropIfExists('procurements');
        Schema::dropIfExists('procurement_types');

        echo "✅ Tables procurement supprimées\n";
    }

    public function down(): void
    {
        //
    }
};
