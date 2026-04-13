<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // Date et horaires
            $table->date('date');
            $table->time('clock_in')->nullable(); // Heure d'arrivée
            $table->time('clock_out')->nullable(); // Heure de départ

            // Pause déjeuner
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->decimal('break_duration', 5, 2)->default(0); // En heures

            // Calculs automatiques
            $table->decimal('total_hours', 5, 2)->default(0); // Heures travaillées
            $table->decimal('regular_hours', 5, 2)->default(0); // Heures normales
            $table->decimal('overtime_hours', 5, 2)->default(0); // Heures supplémentaires

            // Statut
            $table->enum('status', [
                'present',      // Présent
                'absent',       // Absent
                'late',         // En retard
                'half_day',     // Demi-journée
                'on_leave',     // En congé
                'on_mission',   // En mission
                'sick'          // Malade
            ])->default('present');

            // Retard
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);

            // Départ anticipé
            $table->boolean('is_early_departure')->default(false);
            $table->integer('early_departure_minutes')->default(0);

            // Justification
            $table->text('notes')->nullable();
            $table->string('justification_document')->nullable();

            // Validation
            $table->boolean('is_validated')->default(false);
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();

            // Localisation (si pointage mobile)
            $table->string('clock_in_location')->nullable();
            $table->string('clock_out_location')->nullable();
            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->unique(['employee_id', 'date']);
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
