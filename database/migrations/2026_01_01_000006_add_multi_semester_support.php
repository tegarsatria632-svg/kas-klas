<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'semester')) {
                $table->string('semester', 20)->default('Semester 1')->after('role');
            }
            if (!Schema::hasColumn('users', 'status_akademik')) {
                $table->string('status_akademik', 30)->default('Aktif')->after('semester');
            }
            if (!Schema::hasColumn('users', 'tahun_ajaran')) {
                $table->string('tahun_ajaran', 30)->default('2024/2025')->after('status_akademik');
            }
        });

        // 2. Update kas_transactions table
        Schema::table('kas_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('kas_transactions', 'semester')) {
                $table->string('semester', 20)->default('Semester 1')->after('description');
            }
            if (!Schema::hasColumn('kas_transactions', 'tahun_ajaran')) {
                $table->string('tahun_ajaran', 30)->nullable()->after('semester');
            }
        });

        // 3. Update dues_payments table
        Schema::table('dues_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('dues_payments', 'semester')) {
                $table->string('semester', 20)->default('Semester 1')->after('year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['semester', 'status_akademik', 'tahun_ajaran']);
        });

        Schema::table('kas_transactions', function (Blueprint $table) {
            $table->dropColumn(['semester', 'tahun_ajaran']);
        });

        Schema::table('dues_payments', function (Blueprint $table) {
            $table->dropColumn(['semester']);
        });
    }
};
