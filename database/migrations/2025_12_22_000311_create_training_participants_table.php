<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('employee_id');

            // Inscription
            $table->enum('registration_status', [
                'pending',      // En attente
                'approved',     // Approuvée
                'rejected',     // Rejetée
                'waitlist'      // Liste d'attente
            ])->default('pending');

            $table->timestamp('registered_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Participation
            $table->enum('attendance_status', [
                'registered',   // Inscrit
                'present',      // Présent
                'absent',       // Absent
                'partial'       // Présence partielle
            ])->default('registered');

            $table->integer('hours_attended')->default(0);
            $table->text('absence_reason')->nullable();

            // Évaluation de la formation par le participant
            $table->integer('satisfaction_rating')->nullable(); // 1-5
            $table->integer('content_rating')->nullable(); // 1-5
            $table->integer('trainer_rating')->nullable(); // 1-5
            $table->integer('usefulness_rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            $table->text('suggestions')->nullable();

            // Évaluation du participant (par le formateur)
            $table->integer('participation_score')->nullable(); // 1-5
            $table->integer('test_score')->nullable(); // Note /100
            $table->boolean('passed')->nullable();
            $table->text('trainer_comments')->nullable();

            // Certificat
            $table->boolean('certificate_issued')->default(false);
            $table->string('certificate_number')->nullable();
            $table->date('certificate_date')->nullable();
            $table->string('certificate_file')->nullable();

            // Impact et suivi
            $table->text('skills_acquired')->nullable();
            $table->text('application_plan')->nullable(); // Plan d'application des acquis
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->unique(['training_id', 'employee_id']);
            $table->index('registration_status');
            $table->index('attendance_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_participants');
    }
};
