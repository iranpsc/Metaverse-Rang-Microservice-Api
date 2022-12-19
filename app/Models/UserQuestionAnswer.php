<?php

namespace App\Models;

use App\Models\Challenge\Question;
use App\Models\Challenge\QuestionPrize;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserQuestionAnswer extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @param $question_id
     * @param $user_id
     * @return mixed
     */
    public static function userAnsweredToThisQuestion($question_id, $user_id)
    {
        return self::where('question_id', $question_id)->where('user_id', $user_id)->exists();
    }

    public static function userLastAnswer($question_id, $user_id)
    {
        return self::where('question_id', $question_id, $user_id)->latest()->first();
    }

    public static function totalAnswersCount($question_id)
    {
        return self::where('question_id', $question_id)->count();
    }

    public static function eachAnswerVotes($answer_id)
    {
        return self::where('question_answer_id', $answer_id)->count();
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function questionAnswer(): BelongsTo
    {
        return $this->belongsTo(QuestionAnswer::class);
    }

    /**
     * @return BelongsTo
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return HasMany
     */
    public function questionPrizes(): HasMany
    {
        return $this->hasMany(QuestionPrize::class);
    }
}
