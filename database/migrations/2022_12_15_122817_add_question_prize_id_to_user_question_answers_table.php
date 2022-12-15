<?php

use App\Models\Challenge\QuestionPrize;
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
        Schema::table('user_question_answers', function (Blueprint $table) {
            $table->foreignIdFor(QuestionPrize::class)->after('question_answer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_question_answers', function (Blueprint $table) {
            //
        });
    }
};
