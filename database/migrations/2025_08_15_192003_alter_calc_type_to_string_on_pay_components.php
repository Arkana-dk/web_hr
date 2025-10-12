<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pay_components', function (Blueprint $table) {
            // ENUM -> VARCHAR(20) default 'fixed'
            $table->string('calc_type', 20)->default('fixed')->change();
        });
    }

    public function down(): void
    {
        // Kembalikan ke ENUM lama bila perlu
        \DB::statement("
            ALTER TABLE pay_components
            MODIFY calc_type ENUM('fixed','percent','rule')
            NOT NULL DEFAULT 'fixed'
        ");
    }
};
