<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pay_components', function (Blueprint $t) {
            $t->id();

            // Identitas komponen
            $t->string('code')->unique(); // contoh: BASIC, OT_WD, BPJS_JHT_EE, PPH21
            $t->string('name');

            // Skema baru yang dipakai controller/model
            $t->enum('kind', ['earning','deduction','statutory','allowance','reimbursement'])->default('earning');
            // fixed = nominal tetap, percent = persen dari basis (basic/PHDP/UMR), rule = pakai formula/rules engine
            $t->enum('calc_type', ['fixed','percent','rule'])->default('fixed');
            $t->decimal('default_amount', 15, 2)->nullable();

            // (Opsional/legacy) kolom akunting & atribut tambahan
            $t->string('posting_side')->nullable(); // debit|credit (jika perlu integrasi GL)
            $t->string('gl_account')->nullable();
            $t->string('cost_center')->nullable();

            // Efektivitas aturan (biar perubahan regulasi tak perlu ubah kode)
            $t->date('effective_start')->nullable();
            $t->date('effective_end')->nullable();

            // Atribut fleksibel (mis. basis perhitungan, cap, dsb)
            $t->json('attributes')->nullable();

            // Status & catatan
            $t->boolean('active')->default(true);
            $t->text('notes')->nullable();

            $t->timestamps();
            $t->softDeletes();

            // Index tambahan untuk query umum
            $t->index(['kind','active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_components');
    }
};
