<?php

use App\Models\Challenge\Question;
use App\Models\Challenge\UserQuestionAnswer;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Level\Level;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function convertShamsiToGregorian($date): string
{
    $date = \Morilog\Jalali\CalendarUtils::convertNumbers($date, true);
    $date = str_replace('/', '-', $date);
    return \Morilog\Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d', $date)
        ->format('Y-m-d');
}

function getUnansweredQuestionsCount(User $user): int
{
    $answeredQuestions = UserQuestionAnswer::whereUserId($user->id)->select(['id'])->get();
    return Question::whereNotIn('id', $answeredQuestions)->count();
}

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

function getScorePercentageToNextLevel(?Level $level, int $score): int
{
    if (!$level) {
        if ($score == 0) return 0;

        $firstLevel = Level::first();
        return ($score / $firstLevel->score) * 100;
    } else {
        $nextLevel = Level::find($level->id + 1);
        if (is_null($nextLevel)) return 0;
        return ($score / $nextLevel->score) * 100;
    }
}

function hourlyProfitInfo(User $user): int
{
    $profit = FeatureHourlyProfit::whereUserId($user->id)->oldest('dead_line')->first();
    $userDeadLine = $user->variables->withdraw_profit;

    if (is_null($profit)) {
        return 0;
    }

    $daysDiff = $profit->dead_line->diffInDays(now());
    $remainingPercentage = ((int)$userDeadLine - $daysDiff) / $userDeadLine * 100;

    return ($daysDiff > $userDeadLine) ? 100 : $remainingPercentage;
}


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

function createUserPrivacy(User $user)
{

    DB::table('privacies')->insert([
        [
            'user_id' => $user->id,
            'name' => 'nationality',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'fname',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'lname',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'birthdate',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'phone',
            'display' => 0,
        ],
        [
            'user_id' => $user->id,
            'name' => 'email',
            'display' => 0,
        ],
        [
            'user_id' => $user->id,
            'name' => 'address',
            'display' => 0,
        ],
        [
            'user_id' => $user->id,
            'name' => 'about',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'name',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'registered_at',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'position',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'level',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'score',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'licenses',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'license_score',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'avatar',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'occupation',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'education',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'loved_city',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'loved_country',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'loved_language',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'prediction',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'memory',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'passions',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'amoozeshi_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'maskoni_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'tejari_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'gardeshgari_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'fazasabz_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'behdashti_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'edari_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'nemayeshgah_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_golden_keys',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'used_golden_keys',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_golden_keys',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_bronze_keys',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'used_bronze_keys',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_bronze_keys',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_store_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_union_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_taxi_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_amoozeshgah_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'reporter_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'cooporation_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'developer_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'inspection_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'trading_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'lawyer_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'city_council_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'governer_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'ostandar_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'level_one_judge_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'level_two_judge_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'level_three_judge_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'gate_license',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'all_licenses',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'referrals',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'irr_income',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'psc_income',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'complaint',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'warnings',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'commited_crimes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'satisfaction',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'referral_profit',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'irr_transactions',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'psc_transactions',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'blue_transactions',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'yellow_transactions',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'red_transactions',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'sold_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_features',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'sold_products',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_products',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_irr_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_psc_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_yellow_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_blue_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_red_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_satisfaction_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'dynasty_members_photo',
            'display' => 0,
        ],
        [
            'user_id' => $user->id,
            'name' => 'dynasty_members_info',
            'display' => 0,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_satisfaction_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_referral_profit_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_accumulated_capital_reserve_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_data_storage_prizes',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'followers',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'followers_count',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'following',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'following_count',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'violations',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'breaking_laws',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'paid_psc_fine',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'paid_irr_fine',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'life_style',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'negative_score',
            'display' => 1,
        ],
        [
            'user_id' => $user->id,
            'name' => 'code',
            'display' => 1,
        ]
    ]);
}
