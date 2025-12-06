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
        $attachment = '';
        if ($request->hasFile('attachment')) {
            $storedPath = $request->file('attachment')->store('notes', 'public');

            // Remove execution permissions from uploaded file (security measure)
            $fullPath = storage_path('app/public/' . $storedPath);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644);
            }

            $attachment = url('uploads/' . $storedPath);
        }

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
        $attachment = $note->attachment; // Keep existing attachment if no new one uploaded

        if ($request->hasFile('attachment')) {
            $storedPath = $request->file('attachment')->store('notes', 'public');

            // Remove execution permissions from uploaded file (security measure)
            $fullPath = storage_path('app/public/' . $storedPath);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644);
            }

            $attachment = url('uploads/' . $storedPath);
        }

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
