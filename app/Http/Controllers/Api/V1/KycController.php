<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKycRequest;
use App\Http\Requests\UpdateKycRequest;
use App\Http\Resources\KycResource;
use App\Models\Kyc;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

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
        return Kyc::where('user_id', $this->user->id)->exists()
            ? new KycResource($this->user->kyc)
            : [];
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
        $melliCardFile = $request->file('melli_card');
        $provePictureFile = $request->file('prove_picture');

        $melliCardNameToStore = Str::random() . '.' . $melliCardFile->extension();
        $melliCardFile->storeAs('kyc/'.$request->user()->id, $melliCardNameToStore, 'public');

        $provePictureNameToStore = Str::random() . '.' . $provePictureFile->extension();
        $provePictureFile->storeAs('kyc/'.$request->user()->id, $provePictureNameToStore, 'public');

        $melliCardNameToStore = URL::signedRoute('uploads.download', [
            'user' => $request->user()->id,
            'file' => $melliCardNameToStore
        ]);

        $provePictureNameToStore = URL::signedRoute('uploads.download', [
            'user' => $request->user()->id,
            'file' => $provePictureNameToStore
        ]);

        if ($request->hasFile('resume')) {
            $resumeFile = $request->file('resume');
            $resumeNameToStore = Str::random() . '.' . $resumeFile->extension();
            $resumeFile->storeAs('kyc/'.$request->user()->id, $resumeNameToStore, 'public');

            $resumeNameToStore = URL::signedRoute('uploads.download', [
                'user' => $request->user()->id,
                'file' => $provePictureNameToStore
            ]);
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
            $melliCardFile = $request->file('melli_card');
            $melliCardNameToStore = Str::random() . '.' . $melliCardFile->extension();
            $melliCardFile->storeAs('kyc/'.$request->user()->id, $melliCardNameToStore, 'public');
            $melliCardNameToStore = URL::signedRoute('uploads.download', [
                'user' => $request->user()->id,
                'file' => $melliCardNameToStore
            ]);
            $kyc->melli_card = $melliCardNameToStore;
        }

        if ($request->hasFile('prove_picture')) {
            $provePictureFile = $request->file('melli_card');
            $provePictureNameToStore = Str::random() . '.' . $provePictureFile->extension();
            $provePictureFile->storeAs('kyc/' . $this->user->id, $provePictureNameToStore, 'public');
            $provePictureNameToStore = URL::signedRoute('uploads.download', [
                'user' => $request->user()->id,
                'file' => $provePictureNameToStore
            ]);
            $kyc->prove_picture = $provePictureNameToStore;
        }

        if ($request->hasFile('resume')) {
            $resumeFile = $request->file('melli_card');
            $resumeNameToStore = Str::random() . '.' . $resumeFile->extension();
            $resumeFile->store('kyc/' . $this->user->id, $resumeNameToStore, 'public');
            $resumeNameToStore = URL::signedRoute('uploads.download', [
                'user' => $request->user()->id,
                'file' => $provePictureNameToStore
            ]);
            $kyc->resume = $resumeNameToStore;
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

    public function destroy(Kyc $kyc)
    {
        $kyc->delete();
        return response()->noContent();
    }
}
