<?php

namespace App\Notifications;

use App\Helpers\AssetHelper;
use App\Mail\TransactionMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Services\NotificationService;

class TransactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public function __construct(private Order $order)
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
        return NotificationService::getChannels($notifiable, 'transactions');
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new TransactionMail($this->order))
                    ->to($notifiable->email)
                    ->subject('خریددارایی');
    }

    public function toSms($notifiable)
    {
        return [
            'phone' => $notifiable->phone,
            'token' => $this->order->amount,
            'token2' => $this->order->transaction->amount,
            'template' => 'transaction'
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
        if(in_array($this->order->asset, ['yellow', 'blue', 'red']))
        {
            $messgae = sprintf('مقدار %s لیتر رنگ %s به حساب شما واریز گردید!', [
                $this->order->amount,
                AssetHelper::getAssetTitle($this->order->asset)
            ]);
        } else {
            $messgae = sprintf('مقدار %s %s به حساب شما واریز گردید!', [
                $this->order->amount,
                AssetHelper::getAssetTitle($this->order->asset)
            ]);
        }
        return [
            'related-to' => 'transactions',
            'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
            'sender-name' => 'متارنگ',
            'message' => $messgae,
        ];
    }
}
