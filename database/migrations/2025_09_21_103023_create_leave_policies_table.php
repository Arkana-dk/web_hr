<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('leave_policies')) {
            Schema::create('leave_policies', function (Blueprint $t) {
                $t->id();
                $t->foreignId('name')->nullable()->constrained()->nullOnDelete();
                $t->foreignId('pay_group_id')->nullable()->constrained()->nullOnDelete();
                $t->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
                $t->date('effective_start');
                $t->date('effective_end')->nullable();
                $t->json('rules'); // JSON aturan fleksibel
                $t->timestamps();
                $t->index(['pay_group_id','leave_type_id','effective_start'], 'leave_policies_scope_idx');
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('leave_policies');
    }
};
