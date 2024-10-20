<?php

use App\Models\Challenge\Question;
use App\Models\Challenge\UserQuestionAnswer;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Levels\Level;
use App\Models\User;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * Convert Shamsi (Persian) date to Gregorian date.
 *
 * @param string $date The Shamsi date to convert.
 * @return Carbon The converted Gregorian date.
 */
function jalali_to_carbon($date): Carbon
{
    $date = \Morilog\Jalali\CalendarUtils::convertNumbers($date, true);
    return Jalalian::fromFormat('Y/m/d', $date)->toCarbon();
}

/**
 * Convert Shamsi (Persian) date and time to Carbon date.
 *
 * @param string $dateTime The Shamsi date and time to convert.
 * @throws \Exception
 * @return Carbon The converted Gregorian date.
 */
function jalali_date_time_to_carbon($dateTime): Carbon
{
    try {
        $dateTime = \Morilog\Jalali\CalendarUtils::convertNumbers($dateTime, true);
        return Jalalian::fromFormat('Y/m/d H:i:s', $dateTime)->toCarbon();
    } catch (\Exception $e) {
        throw new \Exception('Invalid date time format.');
    }
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
function hourlyProfitInfo(User $user): float
{
    $profit = FeatureHourlyProfit::whereUserId($user->id)->oldest('dead_line')->first();

    if (!$profit) {
        return 0.0;
    }

    $totalSeconds = $profit->updated_at->diffInSeconds($profit->dead_line);
    $secondsPassed = $profit->updated_at->diffInSeconds(now());

    $elapsedPercentage = ($secondsPassed / $totalSeconds) * 100.0;

    return number_format($elapsedPercentage, 2);
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
