<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Challenge\Answer;
use Illuminate\Http\Request;
use App\Models\SystemVariable;
use App\Models\Challenge\Question;
use App\Models\Challenge\UserQuestionAnswer;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;

class ChallengeController extends Controller
{
    public function getTimings()
    {
        return response()->json([
            'data' => [
                'display_ad_interval' => SystemVariable::getByKey('challenge_display_ad_interval') ?? 15,
                'display_question_interval' => SystemVariable::getByKey('challenge_display_question_interval') ?? 15,
                'display_answer_interval' => SystemVariable::getByKey('challenge_display_answer_interval') ?? 15,
                'participants' => UserQuestionAnswer::distinct()->count('user_id'),
                'correct_answers' => $this->getCorrectAnswers(),
                'wrong_answers' => $this->getWrongAnswers(),
            ]
        ]);
    }

    public function getQuestion()
    {
        $question = $this->selectQuestion();
        if ($question) {
            $question->increment('views');
        }
        return $question ? new QuestionResource($question) : null;
    }

    public function answerResult(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'answer_id' => 'required|integer|exists:answers,id',
        ]);

        $question = Question::findOrFail($request->question_id);
        $answer = Answer::findOrFail($request->answer_id);

        if ($answer->question->isNot($question)) {
            throw ValidationException::withMessages([
                'answer_id' => 'Answer is not valid!'
            ]);
        } else {
            $this->authorize('answer', $question);
            UserQuestionAnswer::create([
                'user_id' => $request->user()->id,
                'question_id' => $question->id,
                'answer_id' => $answer->id,
            ]);

            $question->increment('participants');

            if ($answer->isCorrect()) {
                $request->user()->assets->increment('psc', $question->prize);
            }
        }
        return new QuestionResource($question);
    }

    private function getCorrectAnswers()
    {
        return UserQuestionAnswer::whereUserId(request()->user()->id)
            ->where(function (Builder $query) {
                $query->select('is_correct')
                    ->from('answers')
                    ->whereColumn('user_question_answers.answer_id', 'answers.id')
                    ->limit(1);
            }, 1)
            ->count();
    }

    private function getWrongAnswers()
    {
        return UserQuestionAnswer::whereUserId(request()->user()->id)
            ->where(function (Builder $query) {
                $query->select('is_correct')
                    ->from('answers')
                    ->whereColumn('user_question_answers.answer_id', 'answers.id')
                    ->limit(1);
            }, 0)
            ->count();
    }

    private function selectQuestion(): Question|null
    {
        while (true) {
            $question = Question::inRandomOrder()->first();
            if (!$question) {
                break;
            } else {
                $userAnswer = $this->getUserAnswer($question);
                if (!$userAnswer) {
                    break;
                } else {
                    if ($this->checkUserAnswer($userAnswer)) {
                        continue;
                    } else {
                        $question->load('answers');
                        break;
                    }
                }
            }
        }
        return $question;
    }

    private function getUserAnswer(Question $question): UserQuestionAnswer|null
    {
        return UserQuestionAnswer::whereUserId(request()->user()->id)->whereQuestionId($question->id)->first();
    }

    private function checkUserAnswer(UserQuestionAnswer $userAnswer): bool
    {
        return Answer::whereId($userAnswer->answer_id)->first()->isCorrect();
    }
}
