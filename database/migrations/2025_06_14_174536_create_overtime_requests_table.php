<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::create('overtime_requests', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('employee_id');
        $table->date('date');
        $table->time('start_time');
        $table->time('end_time');
        $table->string('day_type'); // Hapus after()
        $table->string('meal_option'); // Hapus after()
        $table->string('transport_route'); // Hapus after()
        $table->text('reason')->nullable();
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->unsignedBigInteger('approved_by')->nullable();
        $table->timestamps();

        // Foreign keys
        $table->foreign('employee_id')
              ->references('id')->on('employees')
              ->onDelete('cascade');

        $table->foreign('approved_by')
              ->references('id')->on('users')
              ->onDelete('set null');
    });
}


    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
