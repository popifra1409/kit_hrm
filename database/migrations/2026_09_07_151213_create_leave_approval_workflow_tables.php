<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ex: chef_immediat, dept_head, nursing_chief, dat, daaf, dmr_dmra
            $table->string('name'); // libellé affiché
            $table->unsignedTinyInteger('order');

            // Comment résoudre QUI doit approuver cette étape pour une demande donnée :
            // 'service_head'    -> le Major/Chef de Service actuel de l'employé (dynamique)
            // 'department_head' -> le chef de département/sous-direction de l'employé (dynamique)
            // 'role'            -> n'importe quel utilisateur ayant ce rôle Spatie (fixe)
            $table->string('resolver_type');
            $table->string('resolver_role')->nullable(); // slug du rôle si resolver_type = role

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->cascadeOnDelete();
            $table->foreignId('leave_approval_step_id')->constrained('leave_approval_steps')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order'); // copie de l'ordre au moment de la création (stabilité historique)

            $table->string('status')->default('pending'); // pending | approved | rejected | skipped
            $table->foreignId('resolved_user_id')->nullable()->constrained('users')->nullOnDelete(); // qui a effectivement agi
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();

            $table->timestamps();

            $table->unique(['leave_id', 'leave_approval_step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_approval_steps');
    }
};
