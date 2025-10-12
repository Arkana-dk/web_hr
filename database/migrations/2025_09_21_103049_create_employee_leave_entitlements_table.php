<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('leave_entitlements')) {
            Schema::create('leave_entitlements', function (Blueprint $t) {
                $t->id();
                $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $t->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
                $t->date('period_start');
                $t->date('period_end');
                $t->decimal('opening_balance', 6, 2)->default(0);
                $t->decimal('accrued', 6, 2)->default(0);
                $t->decimal('used', 6, 2)->default(0);
                $t->decimal('adjustments', 6, 2)->default(0);
                $t->timestamps();
                $t->unique(['employee_id','leave_type_id','period_start','period_end'], 'entitlement_unique_period');
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('leave_entitlements');
    }
};
