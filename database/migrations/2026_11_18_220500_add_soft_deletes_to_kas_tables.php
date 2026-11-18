<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToKasTables extends Migration
{
    public function up()
    {
        Schema::table('kas_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('kas_transactions', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        Schema::table('dues_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('dues_payments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down()
    {
        Schema::table('kas_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('kas_transactions', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('dues_payments', function (Blueprint $table) {
            if (Schema::hasColumn('dues_payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
}
