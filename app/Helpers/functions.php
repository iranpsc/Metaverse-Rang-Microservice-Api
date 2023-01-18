<?php

use App\Constants\FamilyMembersType;
use App\Constants\TicketStatus;
use App\Models\Captcha;
use App\Models\Feature;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Level\Level;
use App\Models\Trade;
use App\Models\BuyFeatureRequest;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Variable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function fee(Feature $feature)
{
    return [
        'psc' => $feature->properties->price_psc * config('rgb.fee'),
        'irr' => $feature->properties->price_irr * config('rgb.fee')
    ];
}

/*
 This function caculates the total price of a Feature including comissions
*/
function totalPrice(Feature $feature, string $type, array $comissions)
{
    switch ($type) {
        case 'buyer':
            return [
                'psc' => $feature->properties->price_psc + $comissions['psc'],
                'irr' => $feature->properties->price_irr + $comissions['irr'],
            ];
            break;
        case 'seller':
            return [
                'psc' => $feature->properties->price_psc - $comissions['psc'],
                'irr' => $feature->properties->price_irr - $comissions['irr'],
            ];
            break;
        default:
            return null;
    }
}


function chargeBuyer(User $buyer, $feature)
{
    $amount = totalPrice($feature, 'buyer', fee($feature));
    $buyer->assets->decrement('psc', $amount['psc']);
    $buyer->assets->decrement('irr', $amount['irr']);
}

function addSeller(User $seller, $feature)
{
    $amount = totalPrice($feature, 'seller', fee($feature));
    $seller->assets->increment('psc', $amount['psc']);
    $seller->assets->increment('irr', $amount['irr']);
}

function iszero($value): bool
{
    return $value == 0;
}

function convertDateToCarbon($date)
{
    $date = \Morilog\Jalali\CalendarUtils::convertNumbers($date, true);
    $date = str_replace('/', '-', $date);
    $date = Carbon::parse($date)->format('Y-m-d');
    $date = \Morilog\Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d', $date)->format('Y-m-d');
    return $date;
}

function isUnderEighteen(User $user)
{
    return $user->kyc->birthdate->diffInYears(now()) < 18;
}

function getFamilyRelationship($relationship)
{
    switch ($relationship) {
        case FamilyMembersType::BROTHER:
            return 'برادر';
            break;
        case FamilyMembersType::FATHER:
            return 'بدر';
            break;
        case FamilyMembersType::MOTHER:
            return 'مادر';
            break;
        case FamilyMembersType::HUSBAND:
            return 'شوهر';
            break;
        case FamilyMembersType::WIFE:
            return 'همسر';
            break;
        case FamilyMembersType::SISTER:
            return 'خواهر';
            break;
        case FamilyMembersType::OWNER:
            return 'صاحب سلسله';
            break;
        case FamilyMembersType::OFFSPRING:
            return 'فرزند';
            break;
    }
}

function currentColorPrice($color)
{
    return Variable::getRate($color);
}

function currentPscPrice()
{
    return Variable::getRate('psc');
}

function validateOtp(User $user, int $code)
{
    $otp = $user->otp->where('otp_reason', 'trade-feature')->first();
    if ($otp->code != $code || $otp->updated_at->diffInMinutes(now()) > 60) return false;
    return true;
}

function ticketDepartmentsTitle($department)
{
    switch ($department) {
        case 'technical_support':
            return 'پشتیبانی فنی';
            break;
        case 'citizens_safety':
            return 'امنیت شهروندان';
            break;
        case 'investment':
            return 'سرمایه گذاری';
            break;
        case 'inspection':
            return 'بازرسی';
            break;
        case 'protection':
            return 'حراست';
            break;
        case 'ztb':
            return 'مدیریت کل ز ت ب';
            break;
    }
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

function getRemainedTimePercentage($date)
{
}

function hourlyProfitInfo(User $user): array
{
    $firstHourlyProfit = FeatureHourlyProfit::with(['feature', 'feature.properties'])->firstWhere('user_id', $user->id);
    if ($firstHourlyProfit) {
        $dead_line = new Carbon($firstHourlyProfit->dead_line);
        $user_withdraw_profit_limit = $user->variables->withdraw_profit * 86400;
        return [
            'percentage' => 100 - floor(($dead_line->diffInSeconds(now()) / $user_withdraw_profit_limit) * 100),
            'karbari' => $firstHourlyProfit->feature->properties->karbari,
        ];
    }
    return [];
}

function getLevelsImages($userLevel)
{
    $images = [];
    if ($userLevel) {
        $levels = Level::orderBy('score')->lazy();
        foreach ($levels as $level) {
            if ($userLevel->score >= $level->score) {
                array_push($images, $level->image?->url);
            }
        }
    }
    return $images;
}

function generate_captcha()
{
    $IMG = imagecreate(130, 50);

    $bgColor = imagecolorallocate($IMG, 255, 255, 255);
    imagefilledrectangle($IMG, 0, 0, 130, 50, $bgColor);

    for ($i = 0; $i < 4; $i++) {
        $bgColorEllipse = imagecolorallocate($IMG, rand($i + 50, 255), rand($i, 255), rand(0, 255));
        imagefilledellipse($IMG, rand(5, 130), rand(0, 50), rand(0, 100), rand(0, 50), $bgColorEllipse);
        imagefilledellipse($IMG, rand(20, 100), rand(0, 40), rand(0, 130), rand(20, 50), $bgColorEllipse);
    }

    $characters = "AaBbCcDdEeFfGgHh1234567890iIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ0123456789";

    $fonts = [
        "fonts/poppins/poppins-v5-latin-300.ttf",
        "fonts/poppins/poppins-v5-latin-500italic.ttf",
        "fonts/poppins/poppins-v5-latin-600.ttf",
        "fonts/poppins/poppins-v5-latin-700.ttf"
    ];

    $txtColor = imagecolorallocate($IMG, 0, 0, 0);

    $phrase = "";
    for ($i = 0; $i < 4; $i++) {
        $selectedFont = $fonts[rand(0, count($fonts) - 1)];
        $font = public_path($selectedFont);
        $character = $characters[rand(0, strlen($characters) - 1)];
        imagettftext($IMG, 18, rand(40, -20), 20 + ($i * 30), 35 + $i, $txtColor, $font, $character);
        $phrase .= $character;
    }

    $captchaPath = public_path('/captcha/');

    if (!file_exists($captchaPath)) {
        mkdir($captchaPath, 0777);
    }

    $captchaFileName = uniqid();
    $captcha = $captchaPath . $captchaFileName . ".jpeg";
    imagejpeg($IMG, $captcha);
    Captcha::updateOrCreate(
        ['ip' => request()->ip()],
        [
            'code' => $phrase,
            'expires_at' => time() + 30,
            'fileName' => $captcha
        ]
    );
}

function getTransactionTitle(Transaction $transaction)
{
    if ($transaction->payable instanceof BuyFeatureRequest) {
        return 'پیشنهاد خرید ملک';
    } elseif ($transaction->payable instanceof Trade) {
        return 'معامله ملک';
    } elseif ($transaction->payable instanceof Order) {
        return 'خرید دارایی';
    }
}

function getTransactionStatus(Transaction $transaction)
{
    return match ($transaction->status) {
        1 => 'موفق',
        -1 => 'ناموفق',
        0 => 'معلق',
    };
}

function createUserPrivacy(User $user)
{

    DB::table('privacies')->insert([
        [
            'user_id' => $user->id,
            'name' => 'nationality',
        ],
        [
            'user_id' => $user->id,
            'name' => 'fname',
        ],
        [
            'user_id' => $user->id,
            'name' => 'lname',
        ],
        [
            'user_id' => $user->id,
            'name' => 'birthdate',
        ],
        [
            'user_id' => $user->id,
            'name' => 'phone',
        ],
        [
            'user_id' => $user->id,
            'name' => 'email',
        ],
        [
            'user_id' => $user->id,
            'name' => 'address',
        ],
        [
            'user_id' => $user->id,
            'name' => 'about',
        ],
        [
            'user_id' => $user->id,
            'name' => 'name',
        ],
        [
            'user_id' => $user->id,
            'name' => 'registered_at',
        ],
        [
            'user_id' => $user->id,
            'name' => 'position',
        ],
        [
            'user_id' => $user->id,
            'name' => 'level',
        ],
        [
            'user_id' => $user->id,
            'name' => 'score',
        ],
        [
            'user_id' => $user->id,
            'name' => 'licenses',
        ],
        [
            'user_id' => $user->id,
            'name' => 'license_score',
        ],
        [
            'user_id' => $user->id,
            'name' => 'avatar',
        ],
        [
            'user_id' => $user->id,
            'name' => 'occupation',
        ],
        [
            'user_id' => $user->id,
            'name' => 'education',
        ],
        [
            'user_id' => $user->id,
            'name' => 'loved_city',
        ],
        [
            'user_id' => $user->id,
            'name' => 'loved_country',
        ],
        [
            'user_id' => $user->id,
            'name' => 'loved_language',
        ],
        [
            'user_id' => $user->id,
            'name' => 'prediction',
        ],
        [
            'user_id' => $user->id,
            'name' => 'memory',
        ],
        [
            'user_id' => $user->id,
            'name' => 'passions',
        ],
        [
            'user_id' => $user->id,
            'name' => 'amoozeshi_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'maskoni_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'tejari_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'gardeshgari_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'fazasabz_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'behdashti_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'edari_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'nemayeshgah_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_golden_keys',
        ],
        [
            'user_id' => $user->id,
            'name' => 'used_golden_keys',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_golden_keys',
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_bronze_keys',
        ],
        [
            'user_id' => $user->id,
            'name' => 'used_bronze_keys',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_bronze_keys',
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_store_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_union_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_taxi_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'establish_amoozeshgah_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'reporter_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'cooporation_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'developer_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'inspection_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'trading_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'lawyer_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'city_council_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'governer_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'ostandar_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'level_one_judge_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'level_two_judge_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'level_three_judge_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'gate_license',
        ],
        [
            'user_id' => $user->id,
            'name' => 'all_licenses',
        ],
        [
            'user_id' => $user->id,
            'name' => 'referrals',
        ],
        [
            'user_id' => $user->id,
            'name' => 'irr_income',
        ],
        [
            'user_id' => $user->id,
            'name' => 'psc_income',
        ],
        [
            'user_id' => $user->id,
            'name' => 'complaint',
        ],
        [
            'user_id' => $user->id,
            'name' => 'warnings',
        ],
        [
            'user_id' => $user->id,
            'name' => 'commited_crimes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'satisfaction',
        ],
        [
            'user_id' => $user->id,
            'name' => 'referral_profit',
        ],
        [
            'user_id' => $user->id,
            'name' => 'irr_transactions',
        ],
        [
            'user_id' => $user->id,
            'name' => 'psc_transactions',
        ],
        [
            'user_id' => $user->id,
            'name' => 'blue_transactions',
        ],
        [
            'user_id' => $user->id,
            'name' => 'yellow_transactions',
        ],
        [
            'user_id' => $user->id,
            'name' => 'red_transactions',
        ],
        [
            'user_id' => $user->id,
            'name' => 'sold_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_features',
        ],
        [
            'user_id' => $user->id,
            'name' => 'sold_products',
        ],
        [
            'user_id' => $user->id,
            'name' => 'bought_products',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_irr_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_psc_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_yellow_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_blue_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_red_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_satisfaction_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'dynasty_members_photo',
        ],
        [
            'user_id' => $user->id,
            'name' => 'dynasty_members_info',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_satisfaction_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_referral_profit_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_accumulated_capital_reserve_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'recieved_dynasty_data_storage_prizes',
        ],
        [
            'user_id' => $user->id,
            'name' => 'followers',
        ],
        [
            'user_id' => $user->id,
            'name' => 'followers_count',
        ],
        [
            'user_id' => $user->id,
            'name' => 'following',
        ],
        [
            'user_id' => $user->id,
            'name' => 'following_count',
        ],
        [
            'user_id' => $user->id,
            'name' => 'violations',
        ],
        [
            'user_id' => $user->id,
            'name' => 'breaking_laws',
        ],
        [
            'user_id' => $user->id,
            'name' => 'paid_psc_fine',
        ],
        [
            'user_id' => $user->id,
            'name' => 'paid_irr_fine',
        ],
        [
            'user_id' => $user->id,
            'name' => 'life_style',
        ],
        [
            'user_id' => $user->id,
            'name' => 'negative_score',
        ],
        [
            'user_id' => $user->id,
            'name' => 'code'
        ]
    ]);
}

function format_number($number): string
{
    if ($number >= 1000 && $number < 1000000) {
        if (($number * 1000) % 1000 > 0) {
            $number = number_format($number / 1000, 3);
        } else {
            $number = number_format($number / 1000);
        }
        return $number . 'K';
    } elseif ($number >= 1000000 && $number < 1000000000) {
        if (($number * 1000000) % 1000000 > 0) {
            $number = number_format($number / 1000000, 3);
        } else {
            $number = number_format($number / 1000000);
        }
        return $number . 'M';
    } elseif ($number < 1000) {
        if (($number * 1000) % 1000 > 0) {
            $number = number_format($number, 3);
        } else {
            $number = number_format($number);
        }
        return $number;
    }
}
