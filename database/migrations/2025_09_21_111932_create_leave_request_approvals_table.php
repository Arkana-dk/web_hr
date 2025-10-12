<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('leave_request_approvals')) {
            Schema::create('leave_request_approvals', function (Blueprint $t) {
                $t->id();
                $t->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
                $t->unsignedInteger('sequence')->default(1);
                $t->string('role')->nullable();      // manager/hr
                $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $t->enum('status', ['PENDING','APPROVED','REJECTED'])->default('PENDING');
                $t->timestamp('acted_at')->nullable();
                $t->timestamps();
                $t->unique(['leave_request_id','sequence']);
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('leave_request_approvals');
    }
};
