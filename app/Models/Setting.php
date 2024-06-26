<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'automatic_logout' => 'integer',
        'privacy' => 'array',
        'notifications' => 'array',
    ];

    protected $attributes = [
        'automatic_logout' => 60,
        'notifications' => '{
            "trades_email": 1,
            "trades_sms": 1,
            "transactions_email": 1,
            "transactions_sms": 1,
            "login_verification_email": 1,
            "login_verification_sms": 1,
            "reports_email": 1,
            "reports_sms": 1,
            "announcements_email": 1,
            "announcements_sms": 1
        }',
        'privacy' => '{
            "nationality": 1,
            "fname": 1,
            "birthdate": 1,
            "phone": 0,
            "email": 0,
            "address": 0,
            "about": 1,
            "name": 1,
            "registered_at": 1,
            "position": 1,
            "level": 1,
            "score": 1,
            "licenses": 1,
            "license_score": 1,
            "avatar": 1,
            "occupation": 1,
            "education": 1,
            "loved_city": 1,
            "loved_country": 1,
            "loved_language": 1,
            "prediction": 1,
            "memory": 1,
            "passions": 1,
            "amoozeshi_features": 1,
            "maskoni_features": 1,
            "tejari_features": 1,
            "gardeshgari_features": 1,
            "fazasabz_features": 1,
            "behdashti_features": 1,
            "edari_features": 1,
            "nemayeshgah_features": 1,
            "bought_golden_keys": 1,
            "used_golden_keys": 1,
            "recieved_golden_keys": 1,
            "bought_bronze_keys": 1,
            "used_bronze_keys": 1,
            "recieved_bronze_keys": 1,
            "establish_store_license": 1,
            "establish_union_license": 1,
            "establish_taxi_license": 1,
            "establish_amoozeshgah_license": 1,
            "reporter_license": 1,
            "cooporation_license": 1,
            "developer_license": 1,
            "inspection_license": 1,
            "trading_license": 1,
            "lawyer_license": 1,
            "city_council_license": 1,
            "governer_license": 1,
            "ostandar_license": 1,
            "level_one_judge_license": 1,
            "level_two_judge_license": 1,
            "level_three_judge_license": 1,
            "gate_license": 1,
            "all_licenses": 1,
            "referrals": 1,
            "irr_income": 1,
            "psc_income": 1,
            "complaint": 1,
            "warnings": 1,
            "commited_crimes": 1,
            "satisfaction": 1,
            "referral_profit": 1,
            "irr_transactions": 1,
            "psc_transactions": 1,
            "blue_transactions": 1,
            "yellow_transactions": 1,
            "red_transactions": 1,
            "sold_features": 1,
            "bought_features": 1,
            "sold_products": 1,
            "bought_products": 1,
            "recieved_irr_prizes": 1,
            "recieved_psc_prizes": 1,
            "recieved_yellow_prizes": 1,
            "recieved_blue_prizes": 1,
            "recieved_red_prizes": 1,
            "recieved_satisfaction_prizes": 1,
            "dynasty_members_photo": 1,
            "dynasty_members_info": 1,
            "recieved_dynasty_satisfaction_prizes": 1,
            "recieved_dynasty_referral_profit_prizes": 1,
            "recieved_dynasty_accumulated_capital_reserve_prizes": 1,
            "recieved_dynasty_data_storage_prizes": 1,
            "followers": 1,
            "followers_count": 1,
            "following": 1,
            "following_count": 1,
            "violations": 1,
            "breaking_laws": 1,
            "paid_psc_fine": 1,
            "paid_irr_fine": 1,
            "life_style": 1,
            "negative_score": 1,
            "code": 1
        }',
    ];

    public static function getChannels(User $user, string $type): array
    {
        $settings = self::where('user_id', $user->id)->select('id', 'user_id', 'notifications')->first();

        return [
            'mail' => $settings->notifications[$type . '_email'],
            'sms' => $user->hasVerifiedPhone() ? $settings->notifications[$type . '_sms'] : 0,
            'broadcast' => 1
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
