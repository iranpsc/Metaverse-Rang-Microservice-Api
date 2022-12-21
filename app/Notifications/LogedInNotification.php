<?php

namespace App\Notifications;

use App\Mail\logedInMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Services\NotificationService;

class LogedInNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $ip;
    public function __construct($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return NotificationService::getChannels($notifiable, 'login');
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new logedInMail($notifiable))
                ->to($notifiable->email)
                ->subject('ورود به حساب کاربری');
    }

    public function toSms($notifiable)
    {
        return [
            'phone' => $notifiable->phone,
            'token' => $this->ip,
            'template' => 'login'
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
