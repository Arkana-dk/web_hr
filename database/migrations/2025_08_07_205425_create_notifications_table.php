<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', [
                'late',
                'early_leave',
                'no_check_in',
                'no_check_out',
                'leave_request',
                'leave_approved',
                'leave_rejected',
                'leave_quota_exceeded',
                'overtime_request',
                'overtime_approved',
                'overtime_rejected',
                'shift_change_request',
                'shift_change_approved',
                'shift_change_rejected',
                'warning',
                'attendance_request',
                'attendance_request_approved',
                'attendance_request_rejected',
                'attendance_reason_submitted',
                'attendance_reason_approved',
                'attendance_reason_rejected'
            ])->index();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
