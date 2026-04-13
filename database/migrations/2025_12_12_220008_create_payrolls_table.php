<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->integer('month'); // 1-12
            $table->integer('year');
            $table->date('payment_date')->nullable();

            // Salaires
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('total_gains', 15, 2)->default(0); // Total des gains
            $table->decimal('total_deductions', 15, 2)->default(0); // Total des retenues

            // Salaires calculés
            $table->decimal('gross_taxable', 15, 2)->default(0); // Salaire imposable
            $table->decimal('gross_cnps', 15, 2)->default(0); // Salaire cotisable CNPS
            $table->decimal('gross_salary', 15, 2)->default(0); // Salaire brut
            $table->decimal('net_salary', 15, 2)->default(0); // Net à payer

            // Cotisations
            $table->decimal('cnps_employee', 15, 2)->default(0); // Part employé
            $table->decimal('cnps_employer', 15, 2)->default(0); // Part employeur
            $table->decimal('irpp', 15, 2)->default(0);
            $table->decimal('cac', 15, 2)->default(0);

            $table->enum('status', ['draft', 'validated', 'paid', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
