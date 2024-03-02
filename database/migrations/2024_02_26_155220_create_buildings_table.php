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
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('building_models');
            $table->foreignId('feature_id')->constrained('features');
            $table->dateTime('construction_start_date');
            $table->dateTime('construction_end_date');
            $table->double('launched_satisfaction')->default(0);
            $table->json('information')->nullable();
            $table->float('rotation')->default(0);
            $table->string('position');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buildings');
    }
};
