<?php

// database/migrations/2025_09_20_000001_add_meta_and_by_to_notifications.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('by_user_id')->nullable()->after('employee_id');
            $table->json('meta')->nullable()->after('message'); // jika MySQL <5.7, ganti ke longText
            $table->index('by_user_id');
            // opsional FK:
            // $table->foreign('by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('notifications', function (Blueprint $table) {
            // kalau pakai FK, drop dulu:
            // $table->dropForeign(['by_user_id']);
            $table->dropIndex(['by_user_id']);
            $table->dropColumn(['by_user_id', 'meta']);
        });
    }
};
