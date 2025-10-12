<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pay_group_components', function (Blueprint $t) {
            $t->id();

            // Relasi utama
            $t->foreignId('pay_group_id')->constrained()->cascadeOnDelete();
            $t->foreignId('pay_component_id')->constrained('pay_components')->cascadeOnDelete();

            // Urutan dan kontrol visibilitas/wajib
            $t->unsignedInteger('sequence')->default(0);   // urutan tampil & hitung
            $t->boolean('mandatory')->default(true);       // komponen wajib di pay run?
            $t->boolean('active')->default(true);          // aktif/non-aktif di group ini
            $t->text('notes')->nullable();

            // (Opsional/legacy akunting; hapus jika tak dipakai)
            // $t->string('posting_side')->nullable();
            // $t->string('gl_account')->nullable();
            // $t->string('cost_center')->nullable();

            $t->timestamps();

            // Integritas & performa
            $t->unique(['pay_group_id', 'pay_component_id']);
            $t->index(['pay_group_id', 'active']);
            $t->index('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_group_components');
    }
};
