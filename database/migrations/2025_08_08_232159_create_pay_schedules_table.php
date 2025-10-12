<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pay_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('frequency'); // monthly|weekly|custom
            $t->unsignedTinyInteger('period_start_day')->nullable();
            $t->unsignedTinyInteger('period_end_day')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    { Schema::dropIfExists('pay_schedules'); }
};
