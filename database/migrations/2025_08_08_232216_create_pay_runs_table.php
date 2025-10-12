<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pay_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pay_group_id')->constrained('pay_groups');
            $t->date('start_date');
            $t->date('end_date');
            $t->string('status'); // draft|simulated|approved|locked|paid|void
            $t->text('note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('locked_at')->nullable();
            $t->timestamps();
            $t->unique(['pay_group_id','start_date','end_date']);
        });
    }
    public function down(): void
    { Schema::dropIfExists('pay_runs'); }
};
