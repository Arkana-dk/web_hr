<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up(): void {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('pay_group_id')
                    ->nullable()
                    ->constrained('pay_groups')
                    ->nullOnDelete()
                    ->index();
            });
        }
        public function down(): void {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pay_group_id');
            });
        }
    };
