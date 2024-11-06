<?php

namespace App\Policies;

use App\Models\ProfileLimitation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tickets.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the ticket.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Ticket $ticket)
    {
        return $ticket->sender->is($user) || $ticket->reciever?->is($user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        $recieverId = request()->input('reciever');

        $profileLimitation = ProfileLimitation::where(function ($query) use ($user, $recieverId) {
            $query->where('limiter_user_id', $recieverId)
                  ->where(function ($query) use ($user, $recieverId) {
                      $query->where('limited_user_id', $user->id)
                            ->orWhere('limited_user_id', $recieverId);
                  });
        })->first();

        if ($profileLimitation && !$profileLimitation->options['send_ticket']) {
            return Response::deny('کاربر مورد نظر امکان دریافت سند از شما را غیر فعال کرده است.', 403);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Ticket $ticket)
    {
        return $ticket->sender->is($user);
    }

    /**
     * Determine whether the user can delete the ticket.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Ticket $ticket)
    {
        return false;
    }

    /**
     * Determine whether the user can respond to the ticket.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function respond(User $user, Ticket $ticket)
    {
        return $ticket->reciever?->is($user) || $ticket->sender->is($user) && $ticket->isOpen();
    }

    /**
     * Determine whether the user can close the ticket.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function close(User $user, Ticket $ticket)
    {
        return $ticket->user->is($user) && $ticket->isOpen();
    }
}
