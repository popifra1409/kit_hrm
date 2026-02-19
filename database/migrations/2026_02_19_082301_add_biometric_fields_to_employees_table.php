<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // QR Code
            $table->string('qr_code_path')->nullable()->after('photo');
            $table->string('qr_code_data')->nullable()->after('qr_code_path');

            // Biométrie
            $table->text('fingerprint_data')->nullable()->after('qr_code_data'); // Données empreintes digitales (JSON)
            $table->boolean('fingerprint_enrolled')->default(false)->after('fingerprint_data');
            $table->timestamp('fingerprint_enrolled_at')->nullable()->after('fingerprint_enrolled');

            // Photo mise à jour (si pas déjà là)
            if (!Schema::hasColumn('employees', 'photo')) {
                $table->string('photo')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'qr_code_path',
                'qr_code_data',
                'fingerprint_data',
                'fingerprint_enrolled',
                'fingerprint_enrolled_at'
            ]);
        });
    }
};
