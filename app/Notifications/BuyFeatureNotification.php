<?php

namespace App\Notifications;

use App\Helpers\FeatureHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Mail\FeatureBoughtMail;
use App\Models\Trade;
use App\Services\NotificationService;

class BuyFeatureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    private $data, $trade;

    public function __construct($data, Trade $trade)
    {
        $this->data = $data;
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
        return (new FeatureBoughtMail($this->data['feature']))
            ->to($notifiable->email)
            ->subject('خریداری ملک');
    }

    public function toSms($notifiable)
    {
        return [
            'phone' => $notifiable->phone,
            'token' => $this->data['id'],
            'token20' => $this->data['buyer'],
            'token10' => $this->data['seller'],
            'template' => $this->data['template']
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        if ($this->trade->seller->code == 'hm-2000000') {
            $message = sprintf(
                '%s لیتر رنگ %s از حساب شما بابت خرید زمین %s برداشت شد.',
                $this->trade->feature->properties->stability,
                $this->trade->feature->getFeatureColor(),
                $this->trade->feature->properties->id,
            );
        } else {
            $message = sprintf(
                'از حساب شما %s psc و %s ریال بابت خرید ملک %s برداشت شد.',
                $this->trade->psc_amount,
                $this->trade->irr_amount,
                $this->trade->feature->properties->id
            );
        }
        return [
            'related-to' => 'transactions',
            'sender-name' => 'متارنگ',
            'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
            'message' => $message
        ];
    }
}
