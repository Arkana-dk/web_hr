<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('setting_overtimes', function (Blueprint $table) {
            $table->enum('paid_in_month', ['current', 'next'])->default('current');
        });
    }

    public function down(): void
    {
        Schema::table('setting_overtimes', function (Blueprint $table) {
            $table->dropColumn('paid_in_month');
        });
    }
};

