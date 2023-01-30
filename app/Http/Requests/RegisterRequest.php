<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                Password::defaults(),
            ],
            'referral' => 'nullable|exists:users,code'
        ];
    }

    public function  messages()
    {
        return [
            'name' => [
                'required' => 'نام خود را وارد کنید',
                'string' => 'نام صحیح نمی باشد'
            ],
            'email' => [
                'required' => 'ایمیل را وارد کنید',
                'email' => 'آدرس ایمیل صحیح نیست',
                'unique' => 'آدرس ایمیل قبلا استفاده شده است'
            ],
            'password.required' => 'رمز عبور را وارد کنید',
            'referral.exists' => 'لینک رفرال صحیح نیست.'
        ];
    }
}
