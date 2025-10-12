<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_components', function (Blueprint $t) {
            $t->id();

            // Relasi
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('pay_component_id')->constrained('pay_components')->cascadeOnDelete();

            // Override nilai
            $t->decimal('override_amount', 15, 2)->nullable();
            $t->decimal('override_rate', 15, 4)->nullable();
            $t->decimal('override_percent', 5, 4)->nullable();
            $t->text('override_formula')->nullable();

            // Periode berlaku
            $t->date('effective_start')->nullable();
            $t->date('effective_end')->nullable();

            // Status
            $t->boolean('active')->default(true);

            $t->timestamps();

            // Index (pakai nama custom biar pendek)
            $t->index(['employee_id', 'pay_component_id'], 'ec_emp_comp_idx');
            $t->index(['employee_id', 'effective_start', 'effective_end'], 'ec_emp_eff_idx');
            $t->index(['pay_component_id', 'effective_start'], 'ec_comp_eff_idx');
            $t->index('active', 'ec_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_components');
    }
};
