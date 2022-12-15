<?php

namespace App\Models\Challenge;

use App\Models\UserQuestionAnswer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionPrize extends Model
{
    use HasFactory;

    /**
     * @return HasMany
     */
    public function challengePrizeList(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChallengePrizeList::class);
   }

    /**
     * @return BelongsTo
     */
    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
   }

    /**
     * @return BelongsTo
     */
    public function userQuestionAnswer(): BelongsTo
    {
        return $this->belongsTo(UserQuestionAnswer::class);
   }
}
