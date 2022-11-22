<?php

namespace App\Http\Controllers\Dynasty;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class ChildernPermissionsController extends Controller
{
    private $supervisor ;
    public function __construct()
    {
        $this->supervisor = Auth::guard('sanctum')->user();
    }

    public function update(request $request, User $user)
    {


        if(! empty($this->supervisor->dynasty->family->familyMembers->where('id', $user->id)->get()) )
        {
            $user->permissions()->update([
               'BFR'  => $request->BFR ,
               'SF'   => $request->SF,
               'W'    => $request->W,
               'JU'   => $request->JU,
               'DM'   => $request->DM ,
               'PIUP' => $request->PIUP,
               'PITC' => $request->PITC,
               'PIC'  => $request->PIC,
               'ESOO' => $request->ESOO,
               'COTB' => $request->COTB,
            ]);
        }
    }
}
