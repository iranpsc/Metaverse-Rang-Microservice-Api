<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Mail\EmailOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
class GetOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    private $code, $phone, $type, $email;

    public function __construct($code, $type = 'sms', $email = null, $phone = null, )
    {
        $this->code = $code;
        $this->phone = $phone;
        $this->type = $type;
        $this->email = $email;
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
        return $this->type == 'sms' ? [SmsChannel::class] : ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new EmailOtp($notifiable, $this->code))
        ->to($this->email)
        ->from('rgb-robot@irpsc.com');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */

    public function toSms($notifiable) {
        return [
            'phone' => is_null($this->phone) ? $notifiable->phone : $this->phone,
            'token' => $this->code,
            'template' => 'verify'
        ];
    }
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
