<?php

// database/migrations/2025_08_16_000000_update_fk_cascade_on_pay_run_details.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // nama FK default Laravel biasanya: pay_run_details_pay_run_item_id_foreign
        Schema::table('pay_run_details', function (Blueprint $table) {
            $table->dropForeign(['pay_run_item_id']);
        });
        Schema::table('pay_run_details', function (Blueprint $table) {
            $table->foreign('pay_run_item_id')
                ->references('id')->on('pay_run_items')
                ->onDelete('cascade'); // <--- penting
        });
    }

    public function down(): void
    {
        Schema::table('pay_run_details', function (Blueprint $table) {
            $table->dropForeign(['pay_run_item_id']);
        });
        Schema::table('pay_run_details', function (Blueprint $table) {
            $table->foreign('pay_run_item_id')
                ->references('id')->on('pay_run_items');
        });
    }
};
