<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKycRequest;
use App\Http\Requests\UpdateKycRequest;
use App\Http\Resources\KycResource;
use App\Models\Kyc;

class KycController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Kyc::class);
    }

    /**
     * Get the current user's kyc info.
     *
     * @return KycResource
     */
    public function index()
    {
        $kyc = request()->user()->kyc;
        return $kyc ? new KycResource($kyc) : null;
    }

    /**
     * Store the current user's kyc info.
     *
     * @param StoreKycRequest $request
     * @return KycResource
     */
    public function store(StoreKycRequest $request)
    {
        $melliCardFile = $request->file('melli_card');

        $melliCard = url('uploads/' . $melliCardFile->store('kyc', 'public'));

        $originalPath = storage_path($request->video['path'] . '/' . $request->video['name']);

        rename($originalPath, storage_path('app/public/kyc/' . $request->video['name']));

        $video = url('uploads/kyc/' . $request->video['name']);

        $kyc = Kyc::create([
            'user_id' => $request->user()->id,
            'fname' => $request->fname,
            'lname' => $request->lname,
            'melli_code' => $request->melli_code,
            'birthdate' => jalali_to_carbon($request->birthdate),
            'melli_card' => $melliCard,
            'province' => $request->province,
            'video' => $video,
            'verify_text_id' => $request->verify_text_id,
        ]);

        return new KycResource($kyc);
    }

    /**
     * Display the specified resource.
     *
     * @param Kyc $kyc
     * @return KycResource
     */
    public function show(Kyc $kyc)
    {
        return new KycResource($kyc);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateKycRequest $request
     * @param Kyc $kyc
     * @return KycResource
     */
    public function update(UpdateKycRequest $request, Kyc $kyc): KycResource
    {
        if ($request->hasFile('melli_card')) {
            $kyc->melli_card = url('uploads/' . $request->file('melli_card')->store('kyc', 'public'));
        }

        if ($request->has('video')) {
            $originalPath = storage_path('app/' . $request->video['path'] . '/' . $request->video['name']);

            rename($originalPath, storage_path('app/public/kyc/' . $request->video['name']));

            $kyc->video = url('uploads/kyc/' . $request->video['name']);
        }

        $kyc->update([
            'melli_card' => $kyc->melli_card,
            'fname' => $request->fname,
            'lname' => $request->lname,
            'melli_code' => $request->melli_code,
            'birthdate' => jalali_to_carbon($request->birthdate),
            'province' => $request->province,
            'status' => 0,
            'errors' => null,
            'video' => $kyc->video,
            'verify_text_id' => $kyc->verify_text_id,
        ]);

        return new KycResource($kyc->fresh());
    }
}
