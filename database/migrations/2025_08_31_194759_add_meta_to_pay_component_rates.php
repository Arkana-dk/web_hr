<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Tambah meta
        Schema::table('pay_component_rates', function (Blueprint $t) {
            if (!Schema::hasColumn('pay_component_rates', 'meta')) {
                // kalau MySQL lama tak support JSON, ganti ke longText()
                $t->json('meta')->nullable()->after('formula');
            }
        });

        // Cek tipe kolom 'rate' saat ini
        $col = DB::selectOne("
            SELECT DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pay_component_rates'
              AND COLUMN_NAME = 'rate'
        ");
        $dataType = $col?->DATA_TYPE;

        // 2) HANYA jika tipe masih string, bersihkan '' -> NULL
        if (in_array($dataType, ['char','varchar','text','mediumtext','longtext'])) {
            DB::statement("UPDATE `pay_component_rates` SET `rate` = NULL WHERE TRIM(`rate`) = ''");
        }

        // 3) Longgarkan tipe & buat nullable (hindari truncation)
        Schema::table('pay_component_rates', function (Blueprint $t) {
            // 18,6 = lega untuk persen kecil & angka besar
            $t->decimal('rate', 18, 6)->nullable()->change();
        });

        // 4) (Opsional) kalau kamu mau NOT NULL, isi NULL jadi 0 lalu kunci
        // DB::table('pay_component_rates')->whereNull('rate')->update(['rate' => 0]);
        // Schema::table('pay_component_rates', function (Blueprint $t) {
        //     $t->decimal('rate', 18, 6)->nullable(false)->change();
        // });

        // 5) Index
        Schema::table('pay_component_rates', function (Blueprint $t) {
            $t->index(['pay_component_id', 'pay_group_id'], 'pcr_component_group_idx');
            $t->index(['effective_start', 'effective_end'], 'pcr_period_idx');
        });
    }

    public function down(): void
    {
        // rollback minimal (opsional)
    }
};
