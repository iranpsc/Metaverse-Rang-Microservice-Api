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
        Schema::table('kyc_errors', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_id',
                'fname_err',
                'lname_err',
                'father_name_err',
                'melli_code_err',
                'province_err',
                'city_err',
                'street_err',
                'number_err',
                'postal_code_err',
                'address_err',
                'melli_card_err',
                'prove_picture_err',
                'resume_err',
            ]);
            $table->morphs('errorable');
            $table->string('key');
            $table->text('value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kyc_errors', function (Blueprint $table) {
            $table->dropMorphs('errorable');
        });
    }
};
