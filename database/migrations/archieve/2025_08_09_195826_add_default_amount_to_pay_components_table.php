<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pay_components', function (Blueprint $table) {
            $table->decimal('default_amount', 15, 2)->nullable()->after('calc_type');
        });
    }

    public function down(): void
    {
        Schema::table('pay_components', function (Blueprint $table) {
            $table->dropColumn('default_amount');
        });
    }
};

