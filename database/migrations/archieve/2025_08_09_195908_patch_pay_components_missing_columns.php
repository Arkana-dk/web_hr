<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pay_components', function (Blueprint $table) {
            if (!Schema::hasColumn('pay_components', 'active')) {
                $table->boolean('active')->default(true)->after('default_amount');
            }
            if (!Schema::hasColumn('pay_components', 'notes')) {
                $table->text('notes')->nullable()->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pay_components', function (Blueprint $table) {
            if (Schema::hasColumn('pay_components', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('pay_components', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};
