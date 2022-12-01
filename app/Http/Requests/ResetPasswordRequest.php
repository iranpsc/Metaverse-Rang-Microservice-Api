<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ResetPasswordRequest extends FormRequest
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
            'old_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    $old_password = request()->user()->password;
                    if (!Hash::check($value, $old_password)) {
                        $fail('رمز عبور قبلی وارد شده صحیح نمی باشد!');
                    }
                }
            ],
            'password' => [
                'required',
                function ($attribute, $value, $fail) {
                    $pass_pattern = "/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z]).{8,}$/";
                    if (!preg_match($pass_pattern, $value)) {
                        $fail('رمز عبور باید حداقل 8 کاراکتر شامل حداقل یک حرف کوچک، یک حرف بزرگ و عدد باشد');
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'old_password.required' => 'رمز عبور قبلی را وارد کنید',
            'password.required' => 'رمز عبور جدید را وارد کنید',
        ];
    }
}
