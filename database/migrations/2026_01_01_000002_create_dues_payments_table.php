<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dues_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kas_transaction_id')->nullable()->constrained('kas_transactions')->nullOnDelete();
            $table->unsignedTinyInteger('week_number'); // 1 - 5
            $table->unsignedTinyInteger('month');       // 1 - 12
            $table->unsignedSmallInteger('year');      // e.g. 2026
            $table->enum('status', ['lunas', 'belum'])->default('belum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues_payments');
    }
};
