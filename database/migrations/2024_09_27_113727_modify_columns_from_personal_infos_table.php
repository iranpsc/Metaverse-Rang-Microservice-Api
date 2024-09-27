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
        Schema::table('personal_infos', function (Blueprint $table) {
            $table->text('prediction')->nullable()->change();
            $table->text('problem_solving')->nullable()->change();
            $table->text('memory')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_infos', function (Blueprint $table) {
            $table->string('prediction')->nullable(false)->change();
            $table->string('problem_solving')->nullable(false)->change();
            $table->string('memory')->nullable(false)->change();
        });
    }
};
