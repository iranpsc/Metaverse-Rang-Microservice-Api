<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'bank_name' => 'required|min:2',
            'shaba_num' => 'required|ir_sheba',
            'card_num'  => 'required|ir_bank_card_number'
        ];
    }

    public function messages()
    {
        return [
            'bank_name.required' => 'نام بانک را وارد کنید.',
            'shaba_num.required' => 'شماره شبا را وارد کنید.',
            'shaba_num.ir_sheba' => 'شماره شبا صحیح نیست.',
            'card_num.required' => 'شماره کارت را وارد کنید.',
            'card_num.ir_bank_card_number' => 'شماره کار صحیح نیست.'
        ];
    }
}
