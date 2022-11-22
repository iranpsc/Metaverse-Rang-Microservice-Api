<?php

use App\Constants\FamilyMembersType;
use App\Models\Feature;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Level\Level;
use App\Models\User;
use App\Models\Variable;
use Carbon\Carbon;

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
    $birthdate = Carbon::parse($user->kyc->birthdate)->format('Y-m-d');
    $birthdate = Carbon::createFromDate($birthdate);
    $now = Carbon::now();
    if ($birthdate->diffInYears($now) < 18) return true;
    return false;
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

function ticketStatusTitle($status)
{
    switch ($status) {
        case 0:
            return 'جدید';
            break;
        case 1:
            return 'پاسخ داده شده';
            break;
        case 2:
            return 'درحال بررسی';
            break;
        case 3:
            return 'بسته شده';
            break;
    }
}

function getScorePercentageToNextLevel(?Level $level, int $score): int
{
    if (!$level) {
        if ($score == 0) return 100;

        $firstLevel = Level::first();
        return ($score / $firstLevel->score) * 100;
    } else {
        $nextLevel = Level::find($level->id + 1);
        return ($score / $nextLevel->score) * 100;
    }
}

function getRemainedTimePercentage($date)
{
}

function hourlyProfitInfo(User $user): array
{
    $firstHourlyProfit = FeatureHourlyProfit::with(['feature', 'feature.properties'])->firstWhere('user_id', $user->id);
    if($firstHourlyProfit) {
        $dead_line = new Carbon($firstHourlyProfit->dead_line);
        $user_withdraw_profit_limit = $user->variables->withdraw_profit * 86400;
        return [
            'percentage' => floor(($dead_line->diffInSeconds(now()) / $user_withdraw_profit_limit) * 100),
            'karbari' => $firstHourlyProfit->feature->properties->karbari,
        ];
    }
    return [];
}

function getLevelsImages($userLevel) {
    $images = [];
    if($userLevel) {
        $levels = Level::orderBy('score')->lazy();
        foreach($levels as $level) {
            if($userLevel->score >= $level->score) {
                array_push($images, $level->image?->url);
            }
        }
    }
    return $images;
}
