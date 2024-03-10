<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Http\Resources\BankAccountResource;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BankAccount::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return BankAccountResource::collection(request()->user()->bankAccounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|min:2',
            'shaba_num' => 'required|ir_sheba|unique:bank_accounts,shaba_num',
            'card_num'  => 'required|ir_bank_card_number|unique:bank_accounts,card_num'
        ]);

        $bankAccount = $request->user()->bankAccounts()->create([
            'bank_name' => $request->bank_name,
            'shaba_num' => $request->shaba_num,
            'card_num' => $request->card_num,
            'status' => 0,
        ]);

        return new BankAccountResource($bankAccount);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function show(BankAccount $bankAccount)
    {
        return new BankAccountResource($bankAccount);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'shaba_num' => 'required|ir_sheba|unique:bank_accounts,shaba_num,' . $bankAccount->id . ',id',
            'card_num'  => 'required|ir_bank_card_number|unique:bank_accounts,card_num,' . $bankAccount->id . ',id'
        ]);

        $bankAccount->update([
            'bank_name' => $request->bank_name,
            'shaba_num' => $request->shaba_num,
            'card_num' => $request->card_num,
            'status' => 0,
            'errors' => null
        ]);

        return new BankAccountResource($bankAccount);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        return response()->noContent();
    }
}
