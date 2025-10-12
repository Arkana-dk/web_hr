<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $t) {
                $t->id();
                $t->string('code', 20)->unique();
                $t->string('name');
                $t->boolean('is_paid')->default(true);
                $t->boolean('requires_attachment')->default(false);
                $t->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('leave_types');
    }
};
