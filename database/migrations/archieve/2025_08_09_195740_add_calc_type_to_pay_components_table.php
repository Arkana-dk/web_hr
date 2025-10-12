<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pay_components', function (Blueprint $table) {
            $table->enum('calc_type', ['fixed','hourly','percent','formula'])
                  ->default('fixed')
                  ->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('pay_components', function (Blueprint $table) {
            $table->dropColumn('calc_type');
        });
    }
};
