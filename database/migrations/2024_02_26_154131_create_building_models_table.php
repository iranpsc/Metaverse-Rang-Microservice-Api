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
        Schema::create('building_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('model_id')->unique()->index()->comment('The ID of the model in 3D Meta API');
            $table->string('name');
            $table->string('sku');
            $table->text('images');
            $table->json('attributes');
            $table->text('file')->index();
            $table->double('required_satisfaction')->default(0);
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
        Schema::dropIfExists('building_models');
    }
};
