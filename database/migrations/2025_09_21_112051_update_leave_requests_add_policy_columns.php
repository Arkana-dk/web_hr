<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('leave_requests', function (Blueprint $t) {
            if (!Schema::hasColumn('leave_requests','leave_type_id')) {
                $t->foreignId('leave_type_id')->nullable()->after('employee_id')->constrained('leave_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('leave_requests','start_date')) $t->date('start_date')->nullable();
            if (!Schema::hasColumn('leave_requests','end_date'))   $t->date('end_date')->nullable();
            if (!Schema::hasColumn('leave_requests','days'))       $t->decimal('days',6,2)->nullable();
            if (!Schema::hasColumn('leave_requests','status'))     $t->string('status', 20)->default('pending')->index();
            if (!Schema::hasColumn('leave_requests','reason'))     $t->text('reason')->nullable();
            if (!Schema::hasColumn('leave_requests','attachment_path')) $t->string('attachment_path')->nullable();
            if (!Schema::hasColumn('leave_requests','approved_by')) $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('leave_requests','approved_at')) $t->timestamp('approved_at')->nullable();
        });
    }
    public function down(): void {
        // balikkan seminimal mungkin (jangan drop kolom yang mungkin sudah dipakai)
    }
};
