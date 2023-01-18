<?php

namespace App\Http\Controllers\Dynasty;

use App\Constants\DebtPaymentStatus;
use App\Constants\FamilyMembersType;
use App\Helpers\AssetHelper;
use App\Http\Requests\CreateDynastyRequest;
use App\Http\Resources\Dynasty\DynastyResource;
use App\Models\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dynasty\IntroductionPrizeResource;
use App\Models\Dynasty\Dynasty;
use App\Notifications\GetOtpNotification;
use Illuminate\Support\Facades\Hash;
use App\Models\Dynasty\DynastyPrize;

class DynastyController extends Controller
{
    public function index(Request $request): JsonResponse|DynastyResource
    {
        $dynasty = Dynasty::whereBelongsTo($request->user())->with([
            'family',
            'family.familyMembers',
            'feature',
            'user'
            ])->first();
        if (is_null($dynasty)) {
            $features =  $request->user()->features
            ->reject(function($feature) {
                return $feature->properties->karbari !== 'm';
            })
            ->map(function($feature) {
                return [
                    'id' => $feature->id,
                    'properties_id' => $feature->properties->id,
                    'stability' => $feature->properties->stability
                ];
            });
            $prizes = DynastyPrize::whereIn( 'member',['father', 'wife', 'mother', 'sister', 'brother', 'offspring'])->get();
            return response()->json([
                'data' => [
                    'user-has-dynasty' => false,
                    'features' => $features,
                    'prizes' => IntroductionPrizeResource::collection($prizes)
                ]
            ], 200);
        }
        return new DynastyResource($dynasty);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param CreateDynastyRequest $request
     * @param Feature $feature
     * @return DynastyResource|JsonResponse
     */
    public function store(Request $request, Feature $feature): DynastyResource|JsonResponse
    {
        $this->authorize('create', [Dynasty::class, $feature]);
        $dynasty = $request->user()->dynasty()->create([
            'feature_id' => $feature->id,
        ]);

        $family = $dynasty->family()->create();

        $family->familyMembers()->create([
            'user_id' => $request->user()->id,
            'relationship' => FamilyMembersType::OWNER
        ]);

        return new DynastyResource($dynasty);
    }

    public function updateDynastyFeature(Dynasty $dynasty, Feature $feature, Request $request)
    {
        $this->authorize('updateDynastyFeature', [$dynasty, $feature]);
        $code = random_int(100000, 999999);
        $dynasty->otp()->create([
            'user_id' => $request->user()->id,
            'code'    => Hash::make($code)
        ]);
        $request->user()->notify(new GetOtpNotification($code));
        return response()->json(['success'=>'کد تایید ارسال گردید. جهت ادامه کد تایید را وارد کنید.'], 200);
    }

    public function verifyUpdateDynastyFeature(Dynasty $dynasty, Feature $feature, Request $request)
    {
        $this->authorize('updateDynastyFeature', [$dynasty, $feature]);

        $this->validate($request, ['code' => 'required|integer']);

        $otp = $dynasty->otp;
        if(Hash::check($request->code, $otp->code)) {
            $currentFeature = $dynasty->feature;
            $dynasty->update(['feature_id' => $feature->id]);
            if($dynasty->updated_at->diffInDays(now()) < 30)
            {
                $request->user()->debts()->create([
                    AssetHelper::getAssetColor($feature) => $currentFeature->properties->stability * 0.01,
                    'status' => DebtPaymentStatus::UNPAID,
                    'reason' => 'update-dynasty-feature',
                ]);
            }
            $currentFeature->properties->update(['label' => 'locked']);
            $otp->delete();
            return response()->json(['success'=>'ملک بنای سلسله با موفقیت تغییر یافت.'], 200);
        }
        return response()->json(['success'=>'کد تایید وارد شده صحیح نمی باشد یا منقضی شده است!'], 404);
    }

    public function resendOtp(Dynasty $dynasty, Feature $feature, Request $request)
    {
        $this->authorize('updateDynastyFeature', [$dynasty, $feature]);
        $code = random_int(100000, 999999);
        $dynasty->otp->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['code' => Hash::make($code)]
        );
        $request->user()->notify(new GetOtpNotification($code));
        return response()->json(['success'=>'کد تایید مجددا ارسال گردید. جهت ادامه کد تایید را وارد کنید.'], 200);
    }
}
