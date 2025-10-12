<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Drop kolom legacy yang tidak ber-FK terlebih dulu (aman)
        Schema::table('leave_requests', function (Blueprint $t) {
            if (Schema::hasColumn('leave_requests', 'type')) $t->dropColumn('type');
            if (Schema::hasColumn('leave_requests', 'additional_file')) $t->dropColumn('additional_file');
        });

        // Helper: drop semua FK untuk kolom tertentu
        $dropFksOn = function (string $table, string $column) {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $column]);

            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        };

        // 2) Hapus FK & kolom user_id (kalau ada)
        if (Schema::hasColumn('leave_requests', 'user_id')) {
            // coba drop dengan nama default dulu (cepat)
            try { Schema::table('leave_requests', fn (Blueprint $t) => $t->dropForeign('leave_requests_user_id_foreign')); } catch (\Throwable $e) {}
            // kalau masih ada, cari di information_schema
            $dropFksOn('leave_requests', 'user_id');

            Schema::table('leave_requests', function (Blueprint $t) {
                if (Schema::hasColumn('leave_requests', 'user_id')) $t->dropColumn('user_id');
            });
        }

        // 3) Hapus FK & kolom reviewed_by (kalau ada)
        if (Schema::hasColumn('leave_requests', 'reviewed_by')) {
            try { Schema::table('leave_requests', fn (Blueprint $t) => $t->dropForeign('leave_requests_reviewed_by_foreign')); } catch (\Throwable $e) {}
            $dropFksOn('leave_requests', 'reviewed_by');

            Schema::table('leave_requests', function (Blueprint $t) {
                if (Schema::hasColumn('leave_requests', 'reviewed_by')) $t->dropColumn('reviewed_by');
            });
        }
    }

    public function down(): void
    {
        // sengaja dikosongkan (kita tak ingin mengembalikan kolom legacy)
    }
};
