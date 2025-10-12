<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Relasi ke user
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->enum('role', ['employee', 'admin', 'superadmin', 'developer'])->default('employee');

            // Data dasar
            $table->string('name');
            $table->string('national_identity_number')->unique();
            $table->string('family_number_card')->unique();
            $table->string('email')->unique();
            $table->enum('gender', ['Laki-laki','Perempuan']);
            $table->string('title')->nullable();
            $table->string('photo')->nullable();

            // Data pribadi
            $table->text('address')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('kk_number')->nullable();
            $table->string('religion')->nullable();
            $table->string('phone')->nullable();
            $table->enum('marital_status', ['Sudah Kawin','Belum Kawin'])->nullable();
            $table->tinyInteger('dependents_count')->default(0);
            $table->string('education')->nullable();

            // Relasi ke jabatan
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('set null');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');

            // Kontrak & gaji
            $table->date('tmt')->nullable(); // Tanggal mulai kerja
            $table->date('contract_end_date')->nullable();
            $table->decimal('salary', 15, 2)->nullable();

            // Bank
            $table->string('bank_name')->default('Mandiri');
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
