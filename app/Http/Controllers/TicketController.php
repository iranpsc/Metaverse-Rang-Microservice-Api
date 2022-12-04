<?php

namespace App\Http\Controllers;

use App\Constants\TicketStatus;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\TicketResponseRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketRecieved;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class TicketController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->user = Auth::guard('sanctum')->user();
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return TicketResource::collection($this->user->tickets);
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
    public function show(User $user, Ticket $ticket): TicketResource
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
        if ($request->reciever_id && $request->department) {
            abort(403, 'عملیات با خطا مواجه شد');
        }

        if ($request->hasFile('attachment')) {
            $path = env('FTP_ENDPOINT') .
                $request->file('attachment')->store('/tickets/' . $this->user->id);
        } else {
            $path = "";
        }

        $ticket = Ticket::create([
            'title' => $request->title,
            'content' => $request->content,
            'attachment' => $path,
            'user_id' => $this->user->id,
            'reciever_id' => isset($request->reciever_id) ? $request->reciever_id : null,
            'department' => $request->department,
            'code' => random_int(100000, 999999),
        ]);

        if (isset($ticket->reciever)) {
            $message = 'تیکتی از طرف ' . $this->user->name . 'دریافت شده است';
            $ticket->reciever->notify(new TicketRecieved($message));
        }

        $ticket->message = 'تیکت با موفقیت ارسال گردید';

        return new TicketResource($ticket);
    }

    /**
     * @param TicketResponseRequest $request
     * @param Ticket $ticket
     * @return TicketResource|JsonResponse
     */
    public function response(TicketResponseRequest $request, Ticket $ticket): TicketResource|JsonResponse
    {
        if ($ticket->isClosed()) {
            return response()->json([
                'error' => 'این تیکت بسته شده است'
            ]);
        }
        if ($request->hasFile('attachment')) {
            $path = env('FTP_ENDPOINT') . $request->file('attachment')->store('/tickets/ticketResponses/' . $ticket->id);
        } else {
            $path = "";
        }
        $ticket->response()->create([
            'response' => $request->response,
            'attachment' => $path,
        ]);

        $ticket->update([
            'status' => TicketStatus::ANSWERED,
            'responser_name' => $request->user()->name,
        ]);

        $message = 'به تیکت شما با شماره ' . $ticket->code . 'پاسخی ارسال شده است';

        $ticket->sender->notify(new TicketRecieved($message));

        $ticket->message = 'پاسخ تیکت با موفقیت ارسال گردید';

        return new TicketResource($ticket);
    }

    /**
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function close(Ticket $ticket): JsonResponse
    {
        $ticket->update(['status' => 3]);
        return response()->json([
            'success' => 'تیکت بسته شد'
        ]);
    }

    /**
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $ticket->delete();
        File::delete(public_path('/uploads/' . $ticket->attachment));
        return Response::json([
            'success' => 'تیکیت با موفقیت حذف شد'
        ]);
    }
}
