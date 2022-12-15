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
use App\Models\Dynasty\Dynasty;
use App\Notifications\GetOtpNotification;
use Illuminate\Support\Facades\Hash;

class DynastyController extends Controller
{
    public function index(): JsonResponse|DynastyResource
    {
        $dynasty = Dynasty::with(['family', 'family.familyMembers'])
            ->where('user_id', request()->user()->id)
            ->first();
        if (!$dynasty) {
            return response()->json(['error' => 'شما سلسله ندارید!'], 404);
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
        if ($request->user()->cannot('createDynasty', $feature)) {
            abort(403, 'این ملک شرایط لازم جهت تاسیس سلسله را ندارد');
        }

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
        $code = random_int(100000, 999999);
        $dynasty->otp()->create([
            'user_id' => $request->user()->id,
            'code' => Hash::make($code)
        ]);
        $request->user()->notify(new GetOtpNotification($code));
        return response()->json(['success'=>'کد تایید ارسال گردید. جهت ادامه کد تایید را وارد کنید.'], 200);
    }

    public function verifyUpdateDynastyFeature(Dynasty $dynasty, Feature $feature, Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|numeric'],
            [
                'code.required' => 'کد تایید را وارد کنید',
                'code.numeric' => 'کد تایید صحیح نیست'
            ]
        );
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
        $code = random_int(100000, 999999);
        $dynasty->otp->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['code' => Hash::make($code)]
        );
        $request->user()->notify(new GetOtpNotification($code));
        return response()->json(['success'=>'کد تایید مجددا ارسال گردید. جهت ادامه کد تایید را وارد کنید.'], 200);
    }
}
