<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pay_run_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pay_run_id')->constrained('pay_runs');
            $t->foreignId('employee_id')->constrained('employees');
            $t->decimal('gross_earnings', 18, 2)->default(0);
            $t->decimal('total_deductions', 18, 2)->default(0);
            $t->decimal('net_pay', 18, 2)->default(0);
            $t->string('result_status')->default('ok'); // ok|warning|error
            $t->json('diagnostics')->nullable();
            $t->timestamps();
            $t->unique(['pay_run_id','employee_id']);
        });
    }
    public function down(): void
    { Schema::dropIfExists('pay_run_items'); }
};