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
    Schema::create('pay_component_rates', function (Blueprint $t) {
        $t->id();
        $t->foreignId('pay_component_id')->constrained('pay_components')->cascadeOnDelete();
        $t->foreignId('pay_group_id')->nullable()->constrained('pay_groups')->nullOnDelete(); // null = global
        $t->string('unit', 20);                  // amount | % | per_day | per_hour
        $t->decimal('rate', 18, 2)->nullable();  // jika pakai formula, rate boleh null
        $t->text('formula')->nullable();         // untuk calc_type = formula (opsional)
        $t->date('effective_start');
        $t->date('effective_end')->nullable();
        $t->timestamps();

        $t->index(['pay_component_id','pay_group_id']);
        $t->index(['effective_start','effective_end']);
    });
}
public function down(): void 
{ 
    Schema::dropIfExists('pay_component_rates'); 
}

};
