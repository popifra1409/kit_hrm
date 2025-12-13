<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('payroll_item_id');
            $table->string('item_name'); // Nom de l'élément
            $table->enum('type', ['gain', 'deduction']);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_subject_to_cnps')->default(false);
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('display_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_lines');
    }
};
