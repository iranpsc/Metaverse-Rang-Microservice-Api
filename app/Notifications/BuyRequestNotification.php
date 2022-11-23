<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
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
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class, 'database'];
    }

    public function toSms($notifiable) {
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
        return [
            sprintf('شما درخواست خریدی مبنی بر خرید زمین با شناسه %s ارسال کرده اید.', $this->data['id'])
        ];
    }
}
