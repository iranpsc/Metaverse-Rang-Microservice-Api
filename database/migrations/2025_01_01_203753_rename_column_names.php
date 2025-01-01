<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->renameColumn('referer_id', 'referrer_id');
        });

        Schema::table('referral_order_histories', function (Blueprint $table) {
            $table->renameColumn('referer_id', 'referrer_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->renameColumn('referrer_id', 'referer_id');
        });

        Schema::table('referral_order_histories', function (Blueprint $table) {
            $table->renameColumn('referrer_id', 'referer_id');
        });
    }
};
