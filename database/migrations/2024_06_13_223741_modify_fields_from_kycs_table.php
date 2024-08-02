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
        Schema::table('kycs', function (Blueprint $table) {
            $table->dropColumn([
                'prove_picture',
                'resume',
                'father_name',
                'city',
                'number',
                'postal_code',
                'address',
                'site',
            ]);

            $table->string('video')->nullable()->after('melli_card');
            $table->text('verify_text')->nullable()->after('video');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kycs', function (Blueprint $table) {
            $table->string('prove_picture')->nullable();
            $table->string('resume')->nullable();
            $table->string('father_name')->nullable();
            $table->string('city')->nullable();
            $table->string('number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('address')->nullable();
            $table->string('site')->nullable();
            $table->dropColumn('video');
            $table->dropColumn('verify_text');
        });
    }
};
