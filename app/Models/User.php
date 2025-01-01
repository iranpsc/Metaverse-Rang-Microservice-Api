<?php

namespace App\Models;

use App\Models\Dynasty\childrenPermission;
use App\Models\Dynasty\Dynasty;
use App\Models\Dynasty\JoinRequest;
use App\Models\Dynasty\RecievedPrize;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Levels\Level;
use App\Models\Levels\RecievedLevelPrize;
use App\Models\Levels\UserActivity;
use App\Models\User\UserEvent;
use App\Models\User\UserVariable;
use App\Notifications\sendPasswordResetNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
use App\Helpers\FeatureIndicators;
use App\Models\Levels\LevelPrize;
use App\Models\Levels\LevelUser;
use App\Models\User\PersonalInfo;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail, Sitemapable
{
    use Notifiable, HasFactory, HasApiTokens;

    /**
     * Get the observable events for the model.
     *
     * @return array
     */
    protected $observables = [
        'followed',
        'traded',
        'deposit',
        'hourReached',
        'registered',
        'logedIn',
        'logedOut'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'referal_link',
        'phone',
        'ip',
        'last_seen',
        'code',
        'referral',
        'score',
        'phone_verified_at',
        'email_verified_at',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
    ];

    /**
     * The attributes with default values.
     *
     * @var array
     */
    protected $attributes = [
        'ip' => '',
        'code' => '',
        'referal_link' => ''
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen'         => 'datetime',
        'code'              => 'string',
        'score'             => 'integer',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * Get route notifications for the Kavenegar channel.
     *
     * @return string
     */
    public function routeNotificationForKavenegar($driver, $notification = null)
    {
        return $this->phone;
    }

    /**
     * Get site map tags for the model.
     *
     * @return Url|string|array
     */
    public function toSitemapTag(): Url|string|array
    {
        $this->load('profilePhotos');

        $faUrl =  Url::create('https://rgb.irpsc.com/fa/citizens/' . $this->code)
            ->setLastModificationDate(Carbon::create($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.8);

        $enUrl =  Url::create('https://rgb.irpsc.com/en/citizens/' . $this->code)
            ->setLastModificationDate(Carbon::create($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.8);

        foreach ($this->profilePhotos as $photo) {
            $faUrl->addImage($photo->url);
            $enUrl->addImage($photo->url);
        }

        return [$faUrl, $enUrl];
    }

    /**
     * Get the user's account security.
     *
     * @return HasOne
     */
    public function accountSecurity()
    {
        return $this->hasOne(AccountSecurity::class);
    }

    /**
     * Get the user's wallet.
     *
     * @return HasOne
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the user's features.
     *
     * @return HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'owner_id');
    }

    /**
     * Get the user's feature requests.
     *
     * @return HasMany
     */
    public function sellRequests(): HasMany
    {
        return $this->hasMany(SellFeatureRequest::class, 'seller_id');
    }

    /**
     * Get the user's buy requests.
     *
     * @return HasMany
     */
    public function buyRequests(): HasMany
    {
        return $this->hasMany(BuyFeatureRequest::class, 'buyer_id');
    }

    /**
     * Get the user's recieved buy requests.
     *
     * @return HasMany
     */
    public function recievedBuyRequests(): HasMany
    {
        return $this->hasMany(BuyFeatureRequest::class, 'seller_id');
    }


    /**
     * Get the user's transactions.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's latest transaction.
     *
     * @return HasOne
     */
    public function latestTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->latestOfMany('created_at');
    }

    /**
     * Get referals of the user.
     *
     * @return HasManyThrough
     */
    public function referals(): HasManyThrough
    {
        return $this->hasManyThrough(
            __CLASS__,
            Referral::class,
            'reference_id',
            'id',
            'id',
            'referer_id'
        );
    }

    /**
     * Get referer of the user.
     *
     * @return bool
     */
    public function has_reference(): bool
    {
        return isset($this->reference);
    }

    /**
     * Get referer of the user.
     *
     * @return HasOneThrough
     */
    public function reference(): HasOneThrough
    {
        return $this->hasOneThrough(
            __CLASS__,
            Referral::class,
            'referrer_id',
            'id',
            'id',
            'reference_id'
        );
    }

    /**
     * Get referral orders of the user.
     *
     * @return HasMany
     */
    public function referralOrderHistories(): HasMany
    {
        return $this->hasMany(ReferralOrderHistory::class, 'reference_id');
    }

    /**
     * Get first order of the user.
     *
     * @return HasOne
     */
    public function firstOrder(): HasOne
    {
        return $this->hasOne(FirstOrder::class);
    }

    /**
     * Get the user's locked assets.
     *
     * @return MorphMany
     */
    public function lockedwallet()
    {
        return $this->hasMany(LockedAsset::class);
    }

    /**
     * Get the user's followers.
     *
     * @return BelongsToMany
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'follows', 'following_id', 'follower_id');
    }

    /**
     * Get users who the user is following.
     *
     * @return BelongsToMany
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'follows', 'follower_id', 'following_id');
    }

    /**
     * Get the user's tickets.
     *
     * @return HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the user's notes.
     *
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the user's kyc.
     *
     * @return HasOne
     */
    public function kyc(): HasOne
    {
        return $this->hasOne(Kyc::class);
    }

    /**
     * Get the user's settings.
     *
     * @return HasOne
     */
    public function settings(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    /**
     * @return HasOne
     */
    public function generalSettings(): HasOne
    {
        return $this->hasOne(GeneralSetting::class);
    }

    // Level Start

    /**
     * Get the user's level
     *
     * @return BelongsToMany
     */
    public function levels()
    {
        return $this->belongsToMany(Level::class)->using(LevelUser::class)->orderByDesc('id');
    }

    /**
     * Get the user's latest level
     *
     * @return \App\Models\Levels\Level|null
     */
    public function getLatestLevelAttribute(): Level|null
    {
        return $this->levels()->first();
    }

    /**
     * Get the user's recieved prizes
     *
     * @return BelongsToMany
     */
    public function recievedLevelPrizes()
    {
        return $this->belongsToMany(LevelPrize::class, 'recieved_level_prizes')->using(RecievedLevelPrize::class);
    }

    // Level End

    /**
     * Get the user's log
     *
     * @return HasOne
     */
    public function log()
    {
        return $this->hasOne(UserLog::class);
    }

    /**
     * Get the user's sells.
     *
     * @return HasMany
     */
    public function sells(): HasMany
    {
        return $this->hasMany(Trade::class, 'seller_id');
    }

    /**
     * Get the user's buys.
     *
     * @return HasMany
     */
    public function buys(): HasMany
    {
        return $this->hasMany(Trade::class, 'buyer_id');
    }

    /**
     * @return void
     */
    public function followed(): void
    {
        $this->fireModelEvent('followed');
    }

    /**
     * Fire the hourReached event.
     *
     * @return void
     */
    public function hourReached(): void
    {
        $this->fireModelEvent('hourReached');
    }

    /**
     * Fire the traded event.
     *
     * @return void
     */
    public function traded()
    {
        $this->fireModelEvent('traded');
    }

    /**
     * Fire the deposit event.
     *
     * @return void
     */
    public function deposit(): void
    {
        $this->fireModelEvent('deposit');
    }

    /**
     * Get the user's activities.
     *
     * @return HasMany
     */
    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Get the user's latest activity.
     *
     * @return HasOne
     */
    public function latestActivity()
    {
        return $this->hasOne(UserActivity::class)->latestOfMany();
    }

    /**
     * Get the user's reports.
     *
     * @return HasMany
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Determine if the user has a verified kyc.
     *
     * @return bool
     */
    public function verified(): bool
    {
        return $this->kyc?->status === 1;
    }

    /**
     * Get the user's debts.
     *
     * @return HasMany
     */
    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    /**
     * Get the user's latest sell request.
     *
     * @return HasOne
     */
    public function latestSellRequest()
    {
        return $this->hasOne(SellFeatureRequest::class, 'seller_id', 'id')->latestOfMany();
    }

    /**
     * Get the user's feature profits.
     *
     * @return HasMany
     */
    public function featureProfits()
    {
        return $this->hasMany(FeatureHourlyProfit::class);
    }

    /**
     * Get the associated variables for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function variables()
    {
        return $this->hasOne(UserVariable::class);
    }

    /**
     * Get the associated personalInfo for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function personalInfo()
    {
        return $this->hasOne(PersonalInfo::class);
    }

    /**
     * Get the associated events for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(UserEvent::class);
    }

    /**
     * Get the latest order for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    /**
     * Get the profile photos for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function profilePhotos()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function latestProfilePhoto()
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Get the associated dynasty for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dynasty(): HasOne
    {
        return $this->hasOne(Dynasty::class);
    }

    /**
     * Get the sent join requests for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sentJoinRequests()
    {
        return $this->hasMany(JoinRequest::class, 'from_user', 'id');
    }

    /**
     * Get the received join requests for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receivedJoinRequests()
    {
        return $this->hasMany(JoinRequest::class, 'to_user', 'id');
    }

    /**
     * Get the associated permissions for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function permissions(): HasOne
    {
        return $this->hasOne(childrenPermission::class);
    }

    /**
     * Get the received dynasty prizes for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recievedDynastyPrizes()
    {
        return $this->hasMany(RecievedPrize::class);
    }

    /**
     * Get the latest reset request for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestResetRequest()
    {
        return $this->hasOne(Reset::class)->latestOfMany();
    }

    /**
     * Send the password reset notification to the user.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $url = 'https://rgb.irpsc.com/metaverse/reset-password?token=' . $token . '?email=' . $this->getEmailForPasswordReset();
        $this->notify(new sendPasswordResetNotification($url, $this));
    }

    /**
     * Get the privacy settings for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function privacy()
    {
        return $this->hasMany(Privacy::class);
    }

    /**
     * Get the bank accounts associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'bankable');
    }

    /**
     * Check if the user is under eighteen years old.
     *
     * @return bool
     */
    public function isUnderEighteen()
    {
        return $this->verified() && $this->kyc?->birthdate->diffInYears(now()) < 18;
    }

    /**
     * Trigger the registered event for the user.
     *
     * @return void
     */
    public function registered()
    {
        $this->fireModelEvent('registered');
    }

    /**
     * Trigger the 'logedIn' event.
     */
    public function logedIn()
    {
        $this->fireModelEvent('logedIn');
    }

    /**
     * Trigger the 'logedOut' event.
     */
    public function logedOut()
    {
        $this->fireModelEvent('logedOut');
    }

    /**
     * Check the color balance based on the given feature.
     *
     * @param Feature $feature The feature to check the color balance for.
     * @return bool True if the color balance is sufficient, false otherwise.
     */
    public function checkColorBalance(Feature $feature)
    {
        return match ($feature->properties->karbari) {
            FeatureIndicators::Tejari   => $this->wallet->red < $feature->properties->stability,
            FeatureIndicators::Maskoni  => $this->wallet->yellow < $feature->properties->stability,
            FeatureIndicators::Amozeshi => $this->wallet->blue < $feature->properties->stability
        };
    }

    /**
     * Check the balance based on the given feature.
     *
     * @param Feature $feature The feature to check the balance for.
     * @return void
     */
    public function checkBalance(Feature $feature)
    {
        $psc_price = $feature->properties->price_psc;
        $irr_price = $feature->properties->price_irr;

        if ($this->wallet->psc < $psc_price + $psc_price * config('rgb.fee')) {
            abort(403, 'موجودی psc شما کافی نمی باشد.');
        } elseif ($this->wallet->irr < $irr_price + $irr_price * config('rgb.fee')) {
            abort(403, 'موجودی ریال شما کافی نمی باشد.');
        }
    }

    /**
     * Get the notification settings for the given type.
     *
     * @param string $type The type of notification settings to retrieve.
     * @return mixed The notification settings.
     */
    public function getNotificationSettings(string $type)
    {
        return Setting::getChannels($this, $type);
    }

    /**
     * The channels the user receives notification broadcasts on.
     * @return string
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.notifications.' . $this->id;
    }

    /**
     * Check user has verified their phone
     *
     * @return bool
     */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone && $this->phone_verified_at;
    }

    /**
     * Check if user is online
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        return $this->last_seen->diffInMinutes(now()) > 2;
    }
}
