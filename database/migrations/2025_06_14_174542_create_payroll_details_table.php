<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')
                  ->constrained('payrolls')
                  ->cascadeOnDelete();
            $table->string('component_name');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('component_type', ['allowance', 'deduction', 'overtime']);
            // Jadikan nullable agar insert tanpa field ini tidak error
            $table->string('effective_month', 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
