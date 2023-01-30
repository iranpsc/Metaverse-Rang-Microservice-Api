<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKycRequest;
use App\Http\Requests\UpdateKycRequest;
use App\Http\Resources\KycResource;
use App\Models\Kyc;
use Illuminate\Support\Facades\Auth;

class KycController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->user = Auth::guard('sanctum')->user();
        $this->authorizeResource(Kyc::class);
    }

    public function index()
    {
        return $this->user->kyc->exists() ? new KycResource($this->user->kyc) : [];
    }

    /**
     * @param Kyc $kyc
     * @return KycResource
     */
    public function show(Kyc $kyc): KycResource
    {
        return new KycResource($kyc);
    }

    public function store(StoreKycRequest $request)
    {
        $melliCardNameToStore = $request->file('melli_card')->store('user/kyc/' . $this->user->id);
        $provePictureNameToStore = $request->file('prove_picture')->store('user/kyc/' . $this->user->id);
        if ($request->hasFile('resume')) {
            $resumeNameToStore = $request->file('resume')->store('user/kyc/' . $this->user->id);
        }

        $kyc = $request->user()->kyc()->create([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'melli_code' => $request->melli_code,
            'birthdate' => convertDateToCarbon($request->birthdate),
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
            $kyc->melli_card = $request->file('melli_card')->store('user/kyc/' . $this->user->id);
        }

        if ($request->hasFile('prove_picture')) {
            $kyc->prove_picture = $request->file('prove_picture')->store('user/kyc/' . $this->user->id);
        }

        if ($request->hasFile('resume')) {
            $kyc->resume = $request->file('resume')->store('user/kyc/' . $this->user->id);
        }

        $kyc->update([
            'melli_card' => $kyc->melli_card,
            'prove_picture' => $kyc->prove_picture,
            'resume' => $kyc->resume,
            'fname' => $request->fname,
            'lname' => $request->lname,
            'father_name' => $request->father_name,
            'melli_code' => $request->melli_code,
            'birthdate' => convertDateToCarbon($request->birthdate),
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

    public function destroy(Kyc $kyc)
    {
        $kyc->delete();
        return response()->noContent();
    }
}
