<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('leave_requests', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->nullable()->after('employee_id');

        // Tambahkan foreign key jika kamu ingin integrity dijaga
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });

    // Optional: isi user_id berdasarkan relasi employee
    DB::statement("
        UPDATE leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        SET lr.user_id = e.user_id
    ");
}

public function down()
{
    Schema::table('leave_requests', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
    });
}

};
