<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\BuyFeatureRequest;
use App\Models\SellFeatureRequest;
use App\Policies\BuyFeatureRequestPolicy;
use App\Policies\SellRequestPolicy;
use App\Mail\VerifyEmail as Mail;
use App\Models\Dynasty\Dynasty;
use App\Models\Dynasty\JoinRequest;
use App\Models\Feature;
use App\Models\Kyc;
use App\Models\Level\Prize;
use App\Models\Ticket;
use App\Models\User;
use App\Models\User\Custom;
use App\Policies\DynastyPolicy;
use App\Policies\FeaturePolicy;
use App\Policies\JoinRequestPolicy;
use App\Policies\KycPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PrizePolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Order::class => OrderPolicy::class,
        SellFeatureRequest::class => SellRequestPolicy::class,
        BuyFeatureRequest::class => BuyFeatureRequestPolicy::class,
        Feature::class => FeaturePolicy::class,
        Ticket::class => TicketPolicy::class,
        Kyc::class => KycPolicy::class,
        Prize::class => PrizePolicy::class,
        Dynasty::class => DynastyPolicy::class,
        User::class => UserPolicy::class,
        Custom::class => UserPolicy::class,
        JoinRequest::class => JoinRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new Mail($url, $notifiable->email))
                ->from('rgb@gmail.com')
                ->to($notifiable->email)
                ->subject('تایید آدرس ایمیل');
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return 'https://rgb.irpsc.com/metaverse/reset-password?token='.$token;
        });
    }
}
