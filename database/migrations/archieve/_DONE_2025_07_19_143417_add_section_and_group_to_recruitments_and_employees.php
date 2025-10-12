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
    Schema::table('recruitments', function (Blueprint $table) {
        $table->unsignedBigInteger('section_id')->nullable()->after('position_id');
        $table->unsignedBigInteger('group_id')->nullable()->after('section_id');

        $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
        $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
    });

    Schema::table('employees', function (Blueprint $table) {
        $table->unsignedBigInteger('section_id')->nullable()->after('position_id');
        $table->unsignedBigInteger('group_id')->nullable()->after('section_id');

        $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
        $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitments_and_employees', function (Blueprint $table) {
            //
        });
    }
};
