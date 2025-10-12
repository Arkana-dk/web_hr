<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recruitments', function (Blueprint $table) {
            $table->enum('role', ['employee', 'admin', 'superadmin', 'developer'])->default('employee');
        });
    }

    public function down(): void
    {
        Schema::table('recruitments', function (Blueprint $table) {
            $table->dropColumn(['role']);
        });
    }
};
