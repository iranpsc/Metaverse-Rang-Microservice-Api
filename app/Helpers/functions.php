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
    // If user has no level yet
    if (!$level) {
        if ($score === 0) {
            return 0;
        }
        return (int)(($score / Level::min('score')) * 100);
    }

    // Find next level score
    $nextLevelScore = Level::where('score', '>', $level->score)
        ->min('score');

    return $nextLevelScore ? (int)(($score / $nextLevelScore) * 100) : 0;
}

/**
 * Get the hourly profit information for a user.
 *
 * @param User $user The user object.
 * @return string
 */
function hourlyProfitInfo(User $user): string
{
    $profit = FeatureHourlyProfit::whereUserId($user->id)
        ->oldest('dead_line')
        ->first();

    if (!$profit) {
        return '0.0';
    }

    $now = now();
    $totalSeconds = $profit->updated_at->diffInSeconds($profit->dead_line);
    $secondsPassed = $profit->updated_at->diffInSeconds($now);

    if ($secondsPassed >= $totalSeconds) {
        return '0.0';
    }

    return number_format(($secondsPassed / $totalSeconds) * 100, 2);
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

/**
 * Format a number to a compact representation (e.g., 1.1K, 1.1M).
 *
 * @param float|int $number The number to format
 * @return string The formatted number
 */
function formatCompactNumber($number): string
{
    if ($number < 1000) {
        return rtrim(rtrim(number_format($number, 3), '0'), '.');
    }

    $units = ['K', 'M', 'B', 'T'];
    $power = floor(log($number, 1000));

    if ($power > count($units)) {
        $power = count($units);
    }

    return rtrim(rtrim(number_format($number / pow(1000, $power), 3), '0'), '.') . $units[$power - 1];
}
