<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Mail\BuyRequestSentMail;
use App\Models\BuyFeatureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class, 'database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new BuyRequestSentMail($this->data['buyRequest']))
            ->to($notifiable->email);
    }

    public function toSms($notifiable)
    {
        return [
            'phone' => $notifiable->phone,
            'token' => $this->data['id'],
            'token2' => $this->data['price_psc'] == 0 ? 0 : number_format($this->data['price_psc'], 0, '.', ','),
            'token3' => $this->data['price_irr'] == 0 ? 0 : number_format($this->data['price_irr'], 0, '.', ','),
            'template' => 'buy-land-request',
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
        if ($this->data['price_psc'] > 0 && $this->data['price_irr'] > 0) {
            $message = sprintf(
                'مبلغ %s psc و %s از حساب شما بابت پیشنهاد خرید ملک %s برداشت شد.',
                $this->data['price_psc'] + ($this->data['price_psc'] * config('rgb.fee')),
                $this->data['price_irr'] + ($this->data['price_irr'] * config('rgb.fee')),
                $this->data['id']
            );
        } elseif ($this->data['price_psc'] > 0) {
            $message = sprintf(
                'مبلغ %s psc از حساب شما بابت پیشنهاد خرید ملک %s برداشت شد.',
                $this->data['price_psc'] + ($this->data['price_psc'] * config('rgb.fee')),
                $this->data['id']
            );
        } elseif ($this->data['price_irr'] > 0) {
            $message = sprintf(
                'مبلغ %s ریال از حساب شما بابت پیشنهاد خرید ملک %s برداشت شد.',
                $this->data['price_irr'] + ($this->data['price_irr'] * config('rgb.fee')),
                $this->data['id']
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
