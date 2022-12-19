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
        $userLastAnswerToThisQuestion = UserQuestionAnswer::userAnsweredToThisQuestion($question->id, auth()->user()->id);
        if ($userLastAnswerToThisQuestion) {
            $userLastAnswer = UserQuestionAnswer::userLastAnswer($question->id, auth()->user()->id);
            if ($userLastAnswer->created_at->addMinutes(10) > now() || $userLastAnswer->updated_at->addMinutes(10) > now()) {
                return response()->json([
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => 'شما به تازگی به این سوال پاسخ داده اید',
                ]);
            }
            $userAnswer = UserQuestionAnswer::create([
                'question_id' => $question->id,
                'question_answer_id' => $questionAnswer->id,
                'user_id' => auth()->user()->id
            ]);
            $answersCount = [];
            $percentages = [];
            $usersCount = UserQuestionAnswer::where('question_id', $question->id)->count();
            foreach ($question->answers as $answer) {
                $voteCount = UserQuestionAnswer::where('question_answer_id', $answer->id)->count();
                $answersCount[] = $voteCount;
            }
            foreach ($answersCount as $item) {
                $percentage = ((int)$item * 100) / $usersCount;
                $percentages[] = $percentage;
            }
            if ($questionAnswer->id != $question->correctAnswer->question_answer_id) {
                return \response()->json([
                    'status' => Response::HTTP_OK,
                    'message' => 'پاسخ شما اشتباه بود',
                    'question-answers' => $question->answers,
                    'question-correct-answer' => $question->correctAnswer,
                    'percentage' => $percentages,
                ]);
            }
            $userPrize = auth()->user()->questionAnswers()->create([
                'question_id' => $question->id,
                'question_answer_id' => $questionAnswer->id,
                'question_prize_id' => $question->questionPrize->first()->id
            ]);
            return \response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'پاسخ شما صحیح بود',
                'question-answers' => $question->answers,
                'question-correct-answer' => $question->correctAnswer,
                'percentage' => $percentages
            ]);
        }

        UserQuestionAnswer::create([
            'user_id' => auth()->user()->id,
            'question_id' => $question->id,
            'question_answer_id' => $questionAnswer->id,
            'question_prize_id' => $question->questionPrize->first() ?? null,
        ]);

        //calculation of answers percentage
        $usersAnsweredCount = UserQuestionAnswer::totalAnswersCount($question->id);
        $votesForEachAnswer = [];
        foreach ($question->answers as $answer) {
            $votesForEachAnswer[] = ((int)UserQuestionAnswer::eachAnswerVotes($answer->id) * 100) / $usersAnsweredCount;
        }
        if ($questionAnswer->id != $question->correctAnswer->id) {
            return \response()->json([
                'message' => 'پاسخ شما اشتباه بود',
                'status' => Response::HTTP_OK,
                'answers' => $question->answers,
                'correct_answer' => $question->correctAnswer,
                'percentages' => $votesForEachAnswer
            ]);
        }
        return \response()->json([
            'message' => 'پاسخ شما صحیح بود',
            'status' => Response::HTTP_OK,
            'answers' => $question->answers,
            'correct_answer' => $question->correctAnswer,
        ]);


    }
}
