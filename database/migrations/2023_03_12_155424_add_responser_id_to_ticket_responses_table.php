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
        Schema::table('ticket_responses', function (Blueprint $table) {
            $table->string('responser_name')->after('attachment');
            $table->unsignedBigInteger('responser_id')->after('responser_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_responses', function (Blueprint $table) {
            $table->dropColumn(['responser_name', 'responser_id']);
        });
    }
};
