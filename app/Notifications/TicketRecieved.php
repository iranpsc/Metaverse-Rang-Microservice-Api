<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRecieved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public function __construct(private Ticket $ticket)
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
            'related-to' => 'tickets',
            'sender-image' => $this->ticket->sender->profilePhotos->last()->url ?? 'https://dl.qzparadise.ir/public/metarang/logo.png',
            'sender-name' => $this->ticket->sender->name,
            'message' => 'تیکتی از طرف ' . $this->ticket->sender->name . 'دریافت شده است',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'related-to' => 'tickets',
            'sender-image' => $this->ticket->sender->profilePhotos->last()->url ?? 'https://dl.qzparadise.ir/public/metarang/logo.png',
            'sender-name' => $this->ticket->sender->name,
            'message' => 'تیکتی از طرف ' . $this->ticket->sender->name . 'دریافت شده است',
        ]);
    }
}
