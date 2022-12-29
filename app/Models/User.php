<?php

namespace App\Models;

use App\Models\Dynasty\childrenPermission;
use App\Models\Dynasty\Dynasty;
use App\Models\Dynasty\JoinRequest;
use App\Models\Dynasty\RecievedPrize;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Level\Level;
use App\Models\Level\UserActivity;
use App\Models\Level\UserLevel;
use App\Models\Level\RecievedLevelPrize;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User\Custom;
use App\Models\User\UserEvent;
use App\Models\User\UserVariable;
use App\Notifications\sendPasswordResetNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasFactory, HasApiTokens;

    protected $observables = [
        'followed',
        'traded',
        'deposit',
        'hourReached'
    ];

    protected $casts = [
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'referal_link',
        'phone',
        'ip',
        'last_seen',
        'code',
        'score',
        'phone_verified_at',
        'email_verified_at'
    ];

    public function accountSecurity()
    {
        return $this->hasOne(AccountSecurity::class);
    }

    public function assets()
    {
        return $this->hasOne(Asset::class);
    }

    public function ownField(Feature $feature)
    {
        return $feature->owner_id == $this->id;
    }

    /**
     * @return HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'owner_id');
    }

    /**
     * @return HasMany
     */
    public function sellRequests(): HasMany
    {
        return $this->hasMany(SellFeatureRequest::class, 'seller_id');
    }

    /**
     * @return HasMany
     */
    public function buyRequests(): HasMany
    {
        return $this->hasMany(BuyFeatureRequest::class, 'buyer_id');
    }

    /**
     * @return HasMany
     */
    public function recievedBuyRequests(): HasMany
    {
        return $this->hasMany(BuyFeatureRequest::class, 'seller_id');
    }


    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasOne
     */
    public function latestTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->latestOfMany();
    }

    // Referals Start

    /**
     * @return HasManyThrough
     */
    public function referals(): HasManyThrough
    {
        return $this->hasManyThrough(
            __CLASS__,
            Referal::class,
            'reference_id',
            'id',
            'id',
            'referer_id'
        );
    }

    /**
     * @return bool
     */
    public function has_reference(): bool
    {
        return isset($this->reference);
    }

    /**
     * @return HasOneThrough
     */
    public function reference(): HasOneThrough
    {
        return $this->hasOneThrough(
            __CLASS__,
            Referal::class,
            'referer_id',
            'id',
            'id',
            'reference_id'
        );
    }

    /**
     * @return HasMany
     */
    public function referalOrderHistories(): HasMany
    {
        return $this->hasMany(ReferalOrderHistory::class, 'reference_id');
    }

    // Referals End

    /**
     * @return HasOne
     */
    public function firstOrder(): HasOne
    {
        return $this->hasOne(FirstOrder::class);
    }

    /**
     * @return MorphMany
     */
    public function lockedAssets()
    {
        return $this->hasMany(LockedAsset::class);
    }

    /**
     * @return BelongsToMany
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'follows', 'following_id', 'follower_id');
    }

    /**
     * @return BelongsToMany
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'follows', 'follower_id', 'following_id');
    }

    /**
     * @return HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function recievedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reciever_id');
    }

    /**
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * @return HasOne
     */
    public function kyc(): HasOne
    {
        return $this->hasOne(Kyc::class);
    }

    /**
     * @return HasOne
     */
    public function settings(): HasOne
    {
        return $this->hasOne(Setting::class, 'user_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function generalSettings(): HasOne
    {
        return $this->hasOne(GeneralSetting::class);
    }

    /**
     * @return HasOneThrough
     */
    public function level(): HasOneThrough
    {
        return $this->hasOneThrough(Level::class, UserLevel::class, 'user_id', 'id', 'id', 'level_id');
    }

    /**
     * @return HasMany
     */
    public function recievedPrizes(): HasMany
    {
        return $this->hasMany(RecievedLevelPrize::class);
    }

    /**
     * @return HasOne
     */
    public function log(): HasOne
    {
        return $this->hasOne(UserLog::class);
    }


    /**
     * @return HasMany
     */
    public function sells(): HasMany
    {
        return $this->hasMany(Trade::class, 'seller_id');
    }

    /**
     * @return HasMany
     */
    public function buys(): HasMany
    {
        return $this->hasMany(Trade::class, 'buyer_id');
    }

    /**
     * @return BelongsToMany
     */
    public function prizes(): BelongsToMany
    {
        return $this->belongsToMany(Prize::class, 'received_prizes', 'user_id', 'prize_id');
    }

    /**
     * @return void
     */
    public function followed(): void
    {
        $this->fireModelEvent('followed');
    }

    /**
     * @return void
     */
    public function hourReached(): void
    {
        $this->fireModelEvent('hourReached');
    }

    public function traded()
    {
        $this->fireModelEvent('traded');
    }

    /**
     * @return void
     */
    public function deposit(): void
    {
        $this->fireModelEvent('deposit');
    }

    /**
     * @return HasMany
     */
    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * @return HasOne
     */
    public function latestActivity()
    {
        return $this->hasOne(UserActivity::class)->latestOfMany();
    }

    /**
     * @return HasMany
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * @return bool
     */
    public function verified(): bool
    {
        if (!empty($this->kyc))
            if ($this->kyc->status == 1)
                return true;
        return false;
    }

    /**
     * @return HasMany
     */
    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    public function latestSellRequest()
    {
        return $this->hasOne(SellFeatureRequest::class, 'seller_id', 'id')->latestOfMany();
    }

    public function featureProfits()
    {
        return $this->hasMany(FeatureHourlyProfit::class);
    }

    public function variables()
    {
        return $this->hasOne(UserVariable::class);
    }

    public function customs()
    {
        return $this->hasOne(Custom::class);
    }

    public function events()
    {
        return $this->hasMany(UserEvent::class);
    }

    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    public function profilePhotos()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    // Dynasty
    /**
     * @return HasOne
     */
    public function dynasty(): HasOne
    {
        return $this->hasOne(Dynasty::class);
    }

    public function sentJoinRequests()
    {
        return $this->hasMany(JoinRequest::class, 'from_user', 'id');
    }

    public function recievedJoinRequests()
    {
        return $this->hasMany(JoinRequest::class, 'to_user', 'id');
    }

    /**
     * @return HasOne
     */
    public function permissions(): HasOne
    {
        return $this->hasOne(childrenPermission::class);
    }

    public function recievedDynastyPrizes()
    {
        return $this->hasMany(RecievedPrize::class);
    }
    //

    public function latestResetRequest()
    {
        return $this->hasOne(Reset::class)->latestOfMany();
    }

    public function sendPasswordResetNotification($token)
    {
        $url = 'https://rgb.irpsc.com/metaverse/reset-password?token=' . $token;
        $this->notify(new sendPasswordResetNotification($url, $this));
    }

    /**
     * @return HasMany
     */
    public function questionAnswers(): HasMany
    {
        return $this->hasMany(UserQuestionAnswer::class);
    }

    public function privacy()
    {
        return $this->hasMany(Privacy::class);
    }

    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'bankable');
    }
}
