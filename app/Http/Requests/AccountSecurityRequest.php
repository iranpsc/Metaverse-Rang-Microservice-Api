<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountSecurityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'phone' => [
                'ir_mobile',
                Rule::requiredIf(is_null(request()->user()->phone)
                || is_null(request()->user()->phone_verified_at)),
            ],
            'time' => 'required|integer|min:5|max:60',
        ];
    }

    public function messages()
    {
        return         [
            'phone.ir_mobile' => 'شماره تلفن صحیح نیست',
            'phone.required' => 'جهت ادامه شماره تلفن همراه خود را وارد کنید',
            'time.required' => 'زمان خاموش بودن را وارد کنید!',
            'time.integer' => 'فرمت زمان صحیح نیست',
            'time.min' => 'کمترین مقدار 5 دقیقه می باشد',
            'time.max' => 'بیشترین مقدار 60 می باشد'
        ];
    }
}
