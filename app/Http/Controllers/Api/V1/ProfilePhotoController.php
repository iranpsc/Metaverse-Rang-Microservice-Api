<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfilePhotoResource;
use App\Models\Image;
use Illuminate\Http\Request;

class ProfilePhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ProfilePhotoResource::make(request()->user()->profilePhotos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:png,jpg,jpeg|max:1024']);
        $url = url('uploads/'.$request->file('image')->store('profile'));
        $image = $request->user()->profilePhotos()->create(['url' => $url]);
        return new ProfilePhotoResource($image);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function show(Image $profilePhoto)
    {
        return new ProfilePhotoResource($profilePhoto);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function destroy(Image $profilePhoto)
    {
        $this->authorize('delete', $profilePhoto);
        $profilePhoto->delete();
        return response()->noContent();
    }
}
