<?php

namespace App\Notifications;

use App\Helpers\AssetHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeatureHourlyProfitDeposit extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private array $data)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
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
            'sender-name' => 'متارنگ',
            'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
            'related-to' => 'transactions',
            'message'  => sprintf(
                'تعداد %s %s به حساب شما بابت سود ساعت شمار حاصل از ملک به شناسه %s واریز گردید.',
                number_format($this->data['amount'], 3),
                AssetHelper::getAssetTitle($this->data['asset']),
                $this->data['feature_properties_id'],
            )
        ];
    }
}
