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
        Schema::table('feature_hourly_profits', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('dead_line');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('feature_hourly_profits', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
