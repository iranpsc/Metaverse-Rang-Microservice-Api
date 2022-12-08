<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

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
                function ($attribute, $value, $fail) {
                    $pass_pattern = "/^(?=.*[A-Z])(?=.*[a-z]).{8,}$/";
                    if (!preg_match($pass_pattern, $value)) {
                        $fail('رمز عبور باید حداقل 8 کاراکتر شامل حداقل یک حرف کوچک، یک حرف بزرگ و یکی از سمبل های !@#$%^&* باشد');
                    }
                },
            ],
            'referral' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $pattern = '/^(?=.*[hm-]).{8,}$/';
                    $reference_user = User::firstWhere('code', $value);
                    if (!preg_match($pattern, $value) || is_null($reference_user)) {
                        $fail('لینک رفرال صحیح نیست!');
                    }
                },
            ],
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
        ];
    }
}
