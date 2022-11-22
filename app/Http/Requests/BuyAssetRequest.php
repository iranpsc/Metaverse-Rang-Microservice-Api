<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuyAssetRequest extends FormRequest
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
            'amount' => 'required|numeric|min:1|max:50000',
            'asset' => 'required|in:psc,red,blue,yellow,irr',
        ];
    }

    public function messages()
    {
        return [
            'asset.required' => 'نوع دارایی را مشخص کنید',
            'asset.in' => 'دارایی باید یا psc, رنگ قرمز، آبی و زرد باشد',
            'amount.required' => 'وارد کردن مبلغ الزامیست',
            'amount.numeric' => 'مبلغ باید عدد باشد',
            'amount.min' => 'کمترین مقدار قابل شارژ psc 1 میباشد',
            'amount.max' => 'بیشترین مقدار قابل شارژ psc 50000 میباشد',
        ];
    }
}
