<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftChangeRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();

            // Pengaju
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');

            // Tanggal pengajuan pindah shift berlaku
            $table->date('date');

            // Shift asal & shift tujuan
            $table->foreignId('from_shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('to_shift_id')->constrained('shifts')->onDelete('cascade');

            // Alasan pengajuan
            $table->text('reason')->nullable();

            // Status pengajuan
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Reviewer
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_change_requests');
    }
}
