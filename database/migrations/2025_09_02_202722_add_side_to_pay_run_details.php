<?php

// database/migrations/2025_09_02_000001_add_side_to_pay_run_details.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('pay_run_details', function (Blueprint $t) {
            if (!Schema::hasColumn('pay_run_details','side')) {
                $t->string('side', 20)->nullable()->index();
            }
        });
    }
    public function down(): void {
        Schema::table('pay_run_details', function (Blueprint $t) {
            if (Schema::hasColumn('pay_run_details','side')) {
                $t->dropColumn('side');
            }
        });
    }
};
