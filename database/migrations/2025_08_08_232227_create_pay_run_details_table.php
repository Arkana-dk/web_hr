<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pay_run_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pay_run_item_id')->constrained('pay_run_items');
            $t->foreignId('pay_component_id')->nullable()->constrained('pay_components');
            $t->string('component_code');
            $t->string('component_type'); // earning|deduction|info
            $t->string('name');
            $t->decimal('quantity', 18, 4)->default(1);
            $t->decimal('rate', 18, 4)->default(0);
            $t->decimal('amount', 18, 2);
            $t->json('source')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    { Schema::dropIfExists('pay_run_details'); }
};
