<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Challenge\Question;
use App\Models\QuestionAnswer;
use App\Models\UserQuestionAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return QuestionResource
     */
    public function index(): QuestionResource
    {
        $question = Question::inRandomOrder()->first();
        //check question is answered or not
        $userAnswer = UserQuestionAnswer::where('question_id', $question->id)->where('user_id', auth()->user()->id)->first();
        if (empty($userAnswer)) {
            // question not answered
            return QuestionResource::make($question);
        } else {
            // question answered and its true answer
            if ($question->correctAnswer->question_answer_id == $userAnswer->question_answer_id) {
                //check its show time or not
                if ($userAnswer->updated_at > now()) {
                    return QuestionResource::make($question);
                } else {
                    return QuestionResource::make(Question::inRandomOrder()->first());
                }
            } else {
                if ($userAnswer->updated_at > now()) {
                    return QuestionResource::make($question);
                } else {
                    return QuestionResource::make(Question::inRandomOrder()->first());
                }
            }
        }
    }


    /**
     * @param Question $question
     * @param QuestionAnswer $questionAnswer
     * @return JsonResponse
     */
    public function answerQuestion(Question $question, QuestionAnswer $questionAnswer): JsonResponse
    {
        $userLastAnswer = UserQuestionAnswer::where('question_id', $question->id)->where('user_id', auth()->user()->id)->latest()->first();
        if ($userLastAnswer->updated_at->addMinutes(10) > now() || $userLastAnswer->created_at->addMinutes(10) > now()) {
            return \response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'شما به تازگی به این سوال پاسخ داده اید',
                'answer-status' => 'recently-answered'
            ]);
        }
      $userAnswer =  $question->userQuestionAnswers()->create([
            'question_answer_id' => $questionAnswer->id,
            'user_id' => auth()->user()->id,
        ]);
        $usersCount = UserQuestionAnswer::where('question_id', $question->id)->count();
        $questionAnswers = $question->answers;
        $answersCount = [];
        $percentages = [];
        foreach ($questionAnswers as $answer) {
            $voteCount = UserQuestionAnswer::where('question_answer_id', $answer->id)->count();
            $answersCount[] = $voteCount;
        }
        foreach ($answersCount as $answer) {
            $percentage = ((int)$answer * 100) / $usersCount;
            $percentages[] = $percentage;
        }
        if ($question->correctAnswer->question_answer_id == $questionAnswer->id) {
            $userAnswer->update([
                'question_prize_id' => $question->questionPrize->id
            ]);
            return response()->json([
                'status' => Response::HTTP_OK,
                'answer-status' => 'correct',
                'message' => 'پاسخ شما صحیح بود',
                'correct-answer' => $question->correctAnswer,
                'percentage' => $percentages,
            ]);
        }
        return response()->json([
            'status' => Response::HTTP_OK,
            'answer-status' => 'wrong',
            'message' => 'پاسخ شما صحیح نبود',
            'correct-answer' => $question->correctAnswer,
            'percentage' => $percentages,
        ]);
    }
}
