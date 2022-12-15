<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Challenge\ChallengePrizeList;
use App\Models\Challenge\Question;
use App\Models\Challenge\QuestionPrize;
use App\Models\SystemVariable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
//        SystemVariable::truncate();
//        SystemVariable::insert([
//            [
//                'name' => 'زمان نمایش پاسخ صحیح',
//                'value' => '30',
//                'slug' => 'show-correct-answer-time'
//            ],
//            [
//                'name' => 'زمان پاسخ دهی به سوال',
//                'slug' => 'answer-question-time',
//                'value' => '30',
//            ],
//            [
//                'name' => 'زمان نمایش تبلیغات',
//                'slug' => 'show-ads-time',
//                'value' => '30'
//            ],
//        ]);
//        $this->call([
//            PrivacySeeder::class,
//        ]);

//        ChallengePrizeList::factory()->count(4)->create();
        QuestionPrize::truncate();
        $questions = Question::all();
        foreach ($questions as $question)
        {
            $question->questionPrize()->create([
                'question_id' => $question->id,
                'challenge_prize_list_id' => ChallengePrizeList::inRandomOrder()->first()->id,
                'amount' => random_int(1000,2000),
            ]);
        }
    }
}
