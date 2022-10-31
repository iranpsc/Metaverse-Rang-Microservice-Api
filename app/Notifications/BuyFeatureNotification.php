<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Mail\FeatureBoughtMail;
use App\Services\NotificationService;

class BuyFeatureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

     public $trade;

    public function __construct($trade)
    {
        $this->trade = $trade;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return NotificationService::getChannels($notifiable, 'trades');
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new FeatureBoughtMail($this->trade))
                    ->to($notifiable->email)
                    ->subject('خریداری ملک');
    }

    public function toSms($notifiable) {
        if($this->trade->seller->code == 'hm-20000')
        {
            $template = 'buy-land-metarang';
        }

        return [
            'phone' => $notifiable->phone,
            'token' => $this->trade->feature->properties->id,
            'token20' => $this->trade->buyer->name,
            'token10' => $this->trade->seller->name,
            'template' => $template ?? 'buy-land-user'
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
