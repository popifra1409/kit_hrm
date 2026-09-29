<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletions', function (Blueprint $table) {
            $table->id();

            // On garde une référence à l'employé (qui reste en base), mais PAS de FK vers
            // users puisque ce compte n'existera plus après la suppression.
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('matricule')->nullable(); // instantané, au cas où l'employé serait lui aussi supprimé un jour
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            $table->enum('reason', ['resignation', 'death', 'retirement', 'other'])->default('other');
            $table->text('notes')->nullable();

            $table->enum('initiated_by', ['admin', 'self'])->default('admin');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletions');
    }
};
