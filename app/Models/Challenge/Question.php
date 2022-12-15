<?php

namespace App\Models\Challenge;

use App\Models\CorrectAnswer;
use App\Models\QuestionAnswer;
use App\Models\UserQuestionAnswer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    use HasFactory;

    /**
     * @return HasMany
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class);
    }

    /**
     * @return HasOne
     */
    public function correctAnswer(): HasOne
    {
        return $this->hasOne(CorrectAnswer::class);
    }

    /**
     * @return HasMany
     */
    public function userQuestionAnswers(): HasMany
    {
        return $this->hasMany(UserQuestionAnswer::class);
    }

    /**
     * @return HasMany
     */
    public function questionPrize(): HasMany
    {
        return $this->hasMany(QuestionPrize::class);
    }
}
