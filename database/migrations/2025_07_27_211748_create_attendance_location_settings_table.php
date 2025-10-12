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
    Schema::create('attendance_location_settings', function (Blueprint $table) {
        $table->id();
        $table->string('location_name'); // contoh: Kantor Pusat
        $table->decimal('latitude', 10, 7);
        $table->decimal('longitude', 10, 7);
        $table->integer('radius')->default(100); // radius dalam meter
        $table->timestamps();
    });
    }    


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_location_settings');
    }
};
