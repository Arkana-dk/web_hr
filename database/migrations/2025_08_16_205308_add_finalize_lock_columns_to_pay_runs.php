<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pay_runs', function (Blueprint $t) {
            if (!Schema::hasColumn('pay_runs', 'finalized_at')) {
                $t->timestamp('finalized_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('pay_runs', 'locked_at')) {
                $t->timestamp('locked_at')->nullable()->after('finalized_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pay_runs', function (Blueprint $t) {
            if (Schema::hasColumn('pay_runs', 'locked_at')) {
                $t->dropColumn('locked_at');
            }
            if (Schema::hasColumn('pay_runs', 'finalized_at')) {
                $t->dropColumn('finalized_at');
            }
        });
    }
};
