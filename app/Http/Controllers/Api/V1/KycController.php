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
     * @return KycResource
     */
    public function index()
    {
        $kyc = request()->user()->kyc;
        return $kyc ? new KycResource($kyc) : null;
    }

    /**
     * Store the current user's kyc info.
     * @param StoreKycRequest $request
     * @return KycResource
     */
    public function store(StoreKycRequest $request)
    {
        $melliCardFile = $request->file('melli_card');
        $provePictureFile = $request->file('prove_picture');

        $melliCardNameToStore = url('uploads/'.$melliCardFile->store('kyc', 'public'));

        $provePictureNameToStore = url('uploads/'.$provePictureFile->store('kyc', 'public'));

        if ($request->hasFile('resume')) {
            $resumeFile = $request->file('resume');
            $resumeNameToStore = url('uploads/'.$resumeFile->store('kyc', 'public'));
        }

        $kyc = $request->user()->kyc()->create([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'melli_code' => $request->melli_code,
            'birthdate' => $request->birthdate,
            'father_name' => $request->father_name,
            'melli_card' => $melliCardNameToStore,
            'prove_picture' => $provePictureNameToStore,
            'resume' => $resumeNameToStore ?? "",
            'province' => $request->province,
            'city' => $request->city,
            'number' => $request->number,
            'postal_code' => $request->postal_code,
            'address' => $request->address,
            'site' => $request->site,
        ]);
        return new KycResource($kyc);
    }


    /**
     * @param StoreKycRequest $request
     * @param Kyc $kyc
     * @return KycResource
     */
    public function update(UpdateKycRequest $request, Kyc $kyc): KycResource
    {
        if ($request->hasFile('melli_card')) {
            $kyc->melli_card = url('uploads/'.$request->file('melli_card')->store('kyc', 'public'));
        }

        if ($request->hasFile('prove_picture')) {
            $kyc->prove_picture = url('uploads/'.$request->file('prove_picture')->store('kyc', 'public'));
        }

        if ($request->hasFile('resume')) {
            $kyc->resume = url('uploads/'.$request->file('resume')->store('kyc', 'public'));
        }

        $kyc->update([
            'melli_card' => $kyc->melli_card,
            'prove_picture' => $kyc->prove_picture,
            'resume' => $kyc->resume,
            'fname' => $request->fname,
            'lname' => $request->lname,
            'father_name' => $request->father_name,
            'melli_code' => $request->melli_code,
            'birthdate' => $request->birthdate,
            'province' => $request->province,
            'city' => $request->city,
            'number' => $request->number,
            'postal_code' => $request->postal_code,
            'address' => $request->address,
            'site' => $request->site,
            'status' => 2,
        ]);
        $kyc->errors()->delete();
        return new KycResource($kyc);
    }
}
