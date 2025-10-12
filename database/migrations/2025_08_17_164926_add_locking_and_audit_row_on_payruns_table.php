<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pay_runs', function (Blueprint $t) {
            if (!Schema::hasColumn('pay_runs','locked_at'))  $t->timestamp('locked_at')->nullable()->after('status');
            if (!Schema::hasColumn('pay_runs','locked_by'))  $t->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('pay_runs','checksum'))   $t->string('checksum', 128)->nullable()->index();
            });

            Schema::create('pay_run_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pay_run_id')->constrained()->cascadeOnDelete();
            $t->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $t->string('action', 50); // SIMULATE_START/END, FINALIZE, REOPEN
            $t->json('before_json')->nullable();
            $t->json('after_json')->nullable();
            $t->timestamps();
            });
                }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
