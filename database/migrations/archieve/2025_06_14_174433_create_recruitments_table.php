<?php

// database/migrations/xxxx_xx_xx_create_recruitments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recruitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('cascade');

            // data personal…
            $table->string('name');
            $table->text('address');
            $table->string('place_of_birth');
            $table->date('date_of_birth');
            $table->string('kk_number');
            $table->string('religion');
            $table->enum('gender',['Laki-laki','Perempuan']);

            // ** FK lagi **
            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->onDelete('set null');
            $table->foreignId('position_id')
                  ->nullable()
                  ->constrained('positions')
                  ->onDelete('set null');

            // kontrak & kontak …
            $table->date('tmt');
            $table->date('contract_end_date');
            $table->string('phone');
            $table->string('email');
            $table->string('password')->nullable();

            // status & pendidikan …
            $table->enum('marital_status',['Sudah Kawin','Belum Kawin']);
            $table->string('education');
            

            // gaji
            $table->decimal('salary',15,2);

            // foto & title …
            $table->string('photo')->nullable();
            $table->string('title')->nullable();

            // bank …
            $table->string('bank_name')->default('Mandiri');
            $table->string('bank_account_name');
            $table->string('bank_account_number');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitments');
    }
};