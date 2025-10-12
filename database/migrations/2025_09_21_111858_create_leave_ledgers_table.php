<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('leave_ledgers')) {
            Schema::create('leave_ledgers', function (Blueprint $t) {
                $t->id();
                $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $t->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
                $t->date('entry_date');
                $t->enum('entry_type', ['EARN','USE','ADJUST','CARRY_IN','EXPIRE']);
                $t->decimal('quantity', 6, 2); // +/-
                $t->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
                $t->string('note')->nullable();
                $t->timestamps();
                $t->index(['employee_id','leave_type_id','entry_date']);
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('leave_ledgers');
    }
};
