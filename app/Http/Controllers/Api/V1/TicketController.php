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
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->user = Auth::guard('sanctum')->user();
        $this->authorizeResource(Ticket::class);
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return TicketResource::collection(Ticket::whereBelongsTo($this->user)->simplePaginate(10));
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function recieved(): AnonymousResourceCollection
    {
        return TicketResource::collection($this->user->recievedTickets);
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
    public function store(CreateTicketRequest $request): TicketResource
    {
        $attachment = $request->hasFile('attachment')
            ? $request->file('attachment')->store('user/tickets/' . $this->user->id)
            : '';

        $ticket = Ticket::create([
            'user_id' => $this->user->id,
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
    public function update(CreateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $attachment = $request->hasFile('attachment')
            ? $request->file('attachment')->store('user/tickets/' . $this->user->id)
            : '';

        $ticket->update([
            'title' => $request->title,
            'content' => $request->content,
            'attachment' => $attachment,
            'status' => TicketStatus::NEW,
        ]);
        return new TicketResource($ticket);
    }

    /**
     * @param TicketResponseRequest $request
     * @param Ticket $ticket
     * @return TicketResource|JsonResponse
     */
    public function response(TicketResponseRequest $request, Ticket $ticket): TicketResource|JsonResponse
    {
        $this->authorize('respond', $ticket);
        $attachment = $request->hasFile('attachment')
        ? $request->file('attachment')->store('user/tickets/'.$this->user->id)
        : '';

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'response' => $request->response,
            'attachment' => $attachment,
        ]);

        $ticket->update([
            'status' => TicketStatus::ANSWERED,
            'responser_name' => $request->user()->name,
        ]);

        $ticket->sender->notify(new TicketRecieved($ticket));

        return new TicketResource($ticket);
    }

    /**
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function close(Ticket $ticket)
    {
        $this->authorize('close', $ticket);
        $ticket->update(['status' => TicketStatus::CLOSED]);
        return response()->noContent();
    }
}
