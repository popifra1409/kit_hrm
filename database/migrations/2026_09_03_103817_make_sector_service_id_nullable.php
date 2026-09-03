<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // ⚠️ Ne remet NOT NULL que si aucun secteur n'a service_id à null,
        // sinon cette ligne échouera (ce qui est volontaire, pour éviter
        // de casser des données existantes).
        Schema::table('sectors', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable(false)->change();
        });
    }
};
