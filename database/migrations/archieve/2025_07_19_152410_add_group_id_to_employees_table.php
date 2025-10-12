<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('employees', function (Blueprint $table) {
        $table->unsignedBigInteger('section_id')->nullable()->after('position_id');
        $table->unsignedBigInteger('group_id')->nullable()->after('section_id');
    });
}

public function down()
{
    Schema::table('employees', function (Blueprint $table) {
        $table->dropColumn(['section_id', 'group_id']);
    });
}

};
