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
        Schema::rename('referals', 'referrals');
        Schema::rename('referal_order_histories', 'referral_order_histories');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('referrals', 'referals');
        Schema::rename('referral_order_histories', 'referal_order_histories');
    }
};
