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
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['categoriable_type', 'visits']);
            $table->renameColumn('categoriable_id', 'video_sub_category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('categoriable_type')->after('id');
            $table->unsignedBigInteger('visits')->after('image');
            $table->renameColumn('video_sub_category_id', 'categoriable_id');
        });
    }
};
