<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // tambahkan kolom baru
            // (seksi DIHAPUS, karena sudah ada)
            $table->string('nik_warga')->nullable();
            $table->string('nkk')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();

            // foreign key
            $table->foreign('group_id')
                  ->references('id')
                  ->on('groups')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // drop FK dulu
            $table->dropForeign('employees_group_id_foreign');

            // lalu drop kolom
            $table->dropColumn(['nik_warga', 'nkk', 'group_id']);
            // kolom 'seksi' TIDAK di-drop karena tidak ditambah di up()
        });
    }
};
