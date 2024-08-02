<?php

namespace App\Models;

use App\Models\Dynasty\childrenPermission;
use App\Models\Dynasty\Dynasty;
use App\Models\Dynasty\JoinRequest;
use App\Models\Dynasty\RecievedPrize;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Level\Level;
use App\Models\Level\RecievedLevelPrize;
use App\Models\Level\UserActivity;
use App\Models\Level\UserLevel;
use App\Models\User\Custom;
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
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail, Sitemapable
{
    use Notifiable, HasFactory, HasApiTokens;

    protected $observables = [
        'followed',
        'traded',
        'deposit',
        'hourReached',
        'registered',
        'logedIn',
        'logedOut'
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
        'referral',
        'score',
        'phone_verified_at',
        'email_verified_at',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
    ];

    protected $attributes = [
        'ip' => '',
        'code' => '',
        'referal_link' => ''
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen'         => 'datetime',
        'code'              => 'string',
        'score'             => 'integer',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];


    public function toSitemapTag(): Url|string|array
    {
        $this->load('profilePhotos');

        $faUrl =  Url::create('https://rgb.irpsc.com/fa/citizen/' . $this->code)
            ->setLastModificationDate(Carbon::create($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.8);

        $enUrl =  Url::create('https://rgb.irpsc.com/en/citizen/' . $this->code)
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
     * Get the user's assets.
     *
     * @return HasOne
     */
    public function assets()
    {
        return $this->hasOne(Asset::class);
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
            Referal::class,
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
            Referal::class,
            'referer_id',
            'id',
            'id',
            'reference_id'
        );
    }

    /**
     * Get referal orders of the user.
     *
     * @return HasMany
     */
    public function referalOrderHistories(): HasMany
    {
        return $this->hasMany(ReferalOrderHistory::class, 'reference_id');
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
    public function lockedAssets()
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
     * Get the user's following.
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
     * Get the user's recieved tickets.
     *
     * @return HasMany
     */
    public function recievedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reciever_id');
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
     * Get the user's general settings.
     *
     * @return HasOne
     */
    public function generalSettings(): HasOne
    {
        return $this->hasOne(GeneralSetting::class);
    }

    /**
     * Get the user's level.
     *
     * @return HasOneThrough
     */
    public function level(): HasOneThrough
    {
        return $this->hasOneThrough(Level::class, UserLevel::class, 'user_id', 'id', 'id', 'level_id');
    }

    /**
     * Get the user's recieved prizes.
     *
     * @return HasMany
     */
    public function recievedPrizes(): HasMany
    {
        return $this->hasMany(RecievedLevelPrize::class);
    }

    /**
     * Get the user's log.
     *
     * @return HasOne
     */
    public function log(): HasOne
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
     * Fire the followed event.
     *
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

    public function latestResetRequest()
    {
        return $this->hasOne(Reset::class)->latestOfMany();
    }

    public function sendPasswordResetNotification($token)
    {
        $url = 'https://rgb.irpsc.com/metaverse/reset-password?token=' . $token . '?email=' . $this->getEmailForPasswordReset();
        $this->notify(new sendPasswordResetNotification($url, $this));
    }

    public function privacy()
    {
        return $this->hasMany(Privacy::class);
    }

    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'bankable');
    }

    public function isUnderEighteen()
    {
        return $this->verified() && $this->kyc?->birthdate->diffInYears(now()) < 18;
    }

    public function registered()
    {
        $this->fireModelEvent('registered');
    }

    public function logedIn()
    {
        $this->fireModelEvent('logedIn');
    }

    public function logedOut()
    {
        $this->fireModelEvent('logedOut');
    }

    public function checkColorBalance(Feature $feature)
    {
        return match ($feature->properties->karbari) {
            FeatureIndicators::Tejari   => $this->assets->red < $feature->properties->stability,
            FeatureIndicators::Maskoni  => $this->assets->yellow < $feature->properties->stability,
            FeatureIndicators::Amozeshi => $this->assets->blue < $feature->properties->stability
        };
    }

    public function checkBalance(Feature $feature)
    {
        $psc_price = $feature->properties->price_psc;
        $irr_price = $feature->properties->price_irr;

        if ($this->assets->psc < $psc_price + $psc_price * config('rgb.fee')) {
            abort(403, 'موجودی psc شما کافی نمی باشد.');
        } elseif ($this->assets->irr < $irr_price + $irr_price * config('rgb.fee')) {
            abort(403, 'موجودی ریال شما کافی نمی باشد.');
        }
    }

    public function getNotificationSettings(string $notificationType)
    {
        return GeneralSetting::getChannels($this, $notificationType);
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
     * @return bool
     */
    public function isOnline(): bool
    {
        return $this->last_seen->diffInMinutes(now()) > 2 ? false : true;
    }
}
