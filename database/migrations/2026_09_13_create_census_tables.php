<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('census_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft | open | closed
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('census_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('census_campaign_id')->constrained('census_campaigns')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('status')->default('submitted'); // submitted | validated | rejected
            $table->json('payload'); // instantané complet : personal + dependents[] + diplomas[]

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->unique(['census_campaign_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('census_submissions');
        Schema::dropIfExists('census_campaigns');
    }
};
