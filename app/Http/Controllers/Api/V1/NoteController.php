<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteRequest;
use App\Http\Resources\NoteResource;
use Illuminate\Http\JsonResponse;
use App\Models\Note;
use Illuminate\Http\Response;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(): mixed
    {
        return NoteResource::collection(request()->user()->notes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param NoteRequest $request
     * @return JsonResponse
     */
    public function store(NoteRequest $request): NoteResource
    {
        $attachment = $request->hasFile('attachment')
            ? url('uploads/'.$request->file('attachment')->store('notes'))
            : '';
        $note = Note::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'content' => $request->content,
            'attachment' => $attachment,
        ]);
        return new NoteResource($note);
    }

    /**
     * Display the specified resource.
     *
     * @param Note $note
     * @return JsonResponse
     */
    public function show(Note $note): NoteResource
    {
        return new NoteResource($note);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param NoteRequest $request
     * @param Note $note
     * @return JsonResponse
     */
    public function update(NoteRequest $request, Note $note): NoteResource
    {
        $attachment = $request->hasFile('attachment')
            ? url('uploads/'.$request->file('attachment')->store('notes'))
            : '';
        $note->update([
            'title' => $request->title,
            'content' => $request->content,
            'attachment' => $attachment,
        ]);

        return new NoteResource($note);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Note $note
     * @return JsonResponse
     */
    public function destroy(Note $note)
    {
        $note->delete();
        return response()->noContent();
    }
}
