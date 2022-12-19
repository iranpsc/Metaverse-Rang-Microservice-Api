<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Mail\SellRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SellRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public $feature;

    public function __construct($feature)
    {
        $this->feature = $feature;
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
        return [SmsChannel::class, 'mail'];
    }

    /**
     * Send SMS Notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */

    public function toMail($notifiable)
    {
        return (new SellRequestMail($this->feature))
            ->to($notifiable->email);
    }

    /**
     * Send SMS Notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */

    public function toSms($notifiable)
    {
        return [
            'phone' => $notifiable->phone,
            'token' => $this->feature->properties->id,
            'template' => 'sell-request',
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
