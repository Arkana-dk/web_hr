<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('leave_requests', function (Blueprint $table) {
        $table->string('additional_file')->nullable()->after('status');
    });
}

public function down()
{
    Schema::table('leave_requests', function (Blueprint $table) {
        $table->dropColumn('additional_file');
    });
}

};
