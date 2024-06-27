<?php

use App\Models\Challenge\Question;
use App\Models\Challenge\UserQuestionAnswer;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Level\Level;
use App\Models\User;

/**
 * Convert Shamsi (Persian) date to Gregorian date.
 *
 * @param string $date The Shamsi date to convert.
 * @return string The converted Gregorian date.
 */
function convertShamsiToGregorian($date): string
{
    $date = \Morilog\Jalali\CalendarUtils::convertNumbers($date, true);
    $date = str_replace('/', '-', $date);
    return \Morilog\Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d', $date)
        ->format('Y-m-d');
}

/**
 * Get the count of unanswered questions for a user.
 *
 * @param User $user The user object.
 * @return int The count of unanswered questions.
 */
function getUnansweredQuestionsCount(User $user): int
{
    $answeredQuestions = UserQuestionAnswer::whereUserId($user->id)->select(['id'])->get();
    return Question::whereNotIn('id', $answeredQuestions)->count();
}

/**
 * Get the title of a relationship based on its key.
 *
 * @param string $relationsip The key of the relationship.
 * @return string The title of the relationship.
 */
function getRelationshipTitle(string $relationsip)
{
    return match ($relationsip) {
        'brother' => 'برادر',
        'sister' => 'خواهر',
        'offspring' => 'فرزند',
        'father' => 'پدر',
        'mother' => 'مادر',
        'husband' => 'شوهر',
        'wife' => 'زن',
    };
}

/**
 * Get the percentage of score required to reach the next level.
 *
 * @param Level|null $level The current level object.
 * @param int $score The user's score.
 * @return int The percentage of score required to reach the next level.
 */
function getScorePercentageToNextLevel(?Level $level, int $score): int
{
    if (!$level) {
        if ($score == 0) {
            return 0;
        }

        $firstLevelScore = Level::min('score');
        return ($score / $firstLevelScore) * 100;
    }

    $nextLevel = Level::where('score', '>', $level->score)->orderBy('score')->first();
    if (!$nextLevel) {
        return 0;
    }

    return ($score / $nextLevel->score) * 100;
}

/**
 * Get the hourly profit information for a user.
 *
 * @param User $user The user object.
 * @return int The hourly profit information.
 */
function hourlyProfitInfo(User $user): int
{
    $profit = FeatureHourlyProfit::whereUserId($user->id)->oldest('dead_line')->first();
    $userDeadLine = $user->variables->withdraw_profit;

    if (is_null($profit)) {
        return 0;
    }

    $daysDiff = $profit->dead_line->diffInDays(now());
    $remainingPercentage = ($userDeadLine - $daysDiff) / $userDeadLine * 100;

    return ($daysDiff > $userDeadLine) ? 100 : $remainingPercentage;
}

/**
 * Get the sub-levels based on the user's level.
 *
 * @param mixed $userLevel The user's level object.
 * @return array The array of sub-levels.
 */
function getSubLevels($userLevel): array
{
    return $userLevel ? Level::where('score', '<', $userLevel->score)->orderBy('score')
        ->get()->map(function ($level) {
            return [
                'id' => $level->id,
                'name' => $level->name,
                'slug' => $level->slug,
                'score' => $level->score,
                'image' => config('app.admin_panel_url') . '/uploads/' . $level->image?->url,
            ];
        })->toArray() : [];
}
