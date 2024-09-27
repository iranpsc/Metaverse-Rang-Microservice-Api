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
        Schema::rename('customs', 'personal_infos');
        Schema::table('personal_infos', function (Blueprint $table) {
            $table->json('passions')->nullable()->after('about');
        });
        Schema::dropIfExists('passions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('personal_infos', 'customs');
        Schema::table('customs', function (Blueprint $table) {
            $table->dropColumn('passions');
        });
    }
};
