<?php

namespace App\Notifications;

use App\Mail\Dynasty\RecieverConfirmationMail;
use App\Mail\Dynasty\SenderConfirmationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class JoinDynastyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public $data;

    public function __construct(array $data)
    {
        $this->afterCommit();
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        switch ($this->data['type']) {
            case 'requester_confirmation_message':
                return (new SenderConfirmationMail($this->data['title'], $this->data['request']))
                    ->to($notifiable->email);
                break;
            case 'reciever_message':
                return (new RecieverConfirmationMail($this->data['title'], $this->data['request']))
                    ->to($notifiable->email);
                break;
            case 'requester_accept_message':
                return (new SenderConfirmationMail($this->data['title'], $this->data['request']))
                    ->to($notifiable->email);
                break;
            case 'reciever_accept_message':
                return (new RecieverConfirmationMail($this->data['title'], $this->data['request']))
                    ->to($notifiable->email);
                break;
            default:
                return [];
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        switch ($this->data['type']) {
            case 'requester_confirmation_message':
                return [
                    'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
                    'sender-name' => 'متارنگ',
                    'message' => $this->data['message']
                ];
                break;
            case 'reciever_message':
                return [
                    'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
                    'sender-name' => 'متارنگ',
                    'message' => $this->data['message']
                ];
            case 'requester_accept_message':
                return [
                    'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
                    'sender-name' => 'متارنگ',
                    'message' => $this->data['message']
                ];
                break;
            case 'reciever_accept_message':
                return [
                    'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
                    'sender-name' => 'متارنگ',
                    'message' => $this->data['message']
                ];
            case 'requester_reject_message':
                return [
                    'sender-image' => 'https://dl.qzparadise.ir/public/metarang/logo.png',
                    'sender-name' => 'متارنگ',
                    $this->data['message']
                ];
            default:
                return [];
        }
    }
}
