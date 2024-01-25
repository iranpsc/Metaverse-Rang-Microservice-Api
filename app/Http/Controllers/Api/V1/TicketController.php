<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Constants\TicketStatus;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\TicketResponseRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketResponse;
use App\Models\User;
use App\Notifications\TicketRecieved;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ticket::class);
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return TicketResource::collection(
            Ticket::whereBelongsTo(request()->user())
                ->orderByDesc('updated_at')
                ->simplePaginate(10)
        );
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function recieved(): AnonymousResourceCollection
    {
        return TicketResource::collection(
            Ticket::whereRecieverId(request()->user()->id)->orderByDesc('updated_at')->simplePaginate(10)
        );
    }

    /**
     * @param User $user
     * @param Ticket $ticket
     * @return TicketResource
     */
    public function show(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket);
    }

    public function view(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket);
    }

    /**
     * @param CreateTicketRequest $request
     * @param User $user
     * @return TicketResource
     */
    public function store(CreateTicketRequest $request)
    {
        $attachment = $request->hasFile('attachment')
            ? url('uploads/' . $request->file('attachment')->store('tickets', 'public'))
            : '';

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'content' => $request->content,
            'attachment' => $attachment,
            'reciever_id' => $request->reciever,
            'department' => $request->department,
            'code' => random_int(100000, 999999),
        ]);

        if (isset($ticket->reciever)) {
            $ticket->reciever->notify(new TicketRecieved($ticket));
        }
        return new TicketResource($ticket);
    }

    /**
     * @param CreateTicketRequest $request
     * @param User $user
     * @return TicketResource
     */
    public function update(CreateTicketRequest $request, Ticket $ticket)
    {
        $attachment = $request->hasFile('attachment')
            ? url('uploads/' . $request->file('attachment')->store('tickets'))
            : '';

        $ticket->update([
            'title' => $request->title,
            'content' => $request->content,
            'attachment' => $attachment,
            'status' => TicketStatus::NEW,
        ]);
        return new TicketResource($ticket->refresh());
    }

    /**
     * @param TicketResponseRequest $request
     * @param Ticket $ticket
     * @return TicketResource|JsonResponse
     */
    public function response(TicketResponseRequest $request, Ticket $ticket)
    {
        $this->authorize('respond', $ticket);
        $attachment = $request->hasFile('attachment')
            ? url('uploads/' . $request->file('attachment')->store('tickets'))
            : '';

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'response' => $request->response,
            'attachment' => $attachment,
            'responser_name' => $request->user()->name,
            'responser_id' => $request->user()->id,
        ]);

        $ticket->update(['status' => TicketStatus::ANSWERED]);

        $ticket->sender->notify(new TicketRecieved($ticket));

        return new TicketResource($ticket->refresh());
    }

    /**
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function close(Ticket $ticket)
    {
        $this->authorize('close', $ticket);
        $ticket->update(['status' => TicketStatus::CLOSED]);
        return new TicketResource($ticket->refresh());
    }
}
