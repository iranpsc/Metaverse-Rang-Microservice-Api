<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
                'old_password' => 'required',
                'password' => 'required|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'old_password.required' => 'رمز عبور را وارد کنید',
            'password.required' => 'رمز عبور جدید را وارد کنید',
            'password.confirmed' => 'رمز عبور جدید با تکرار آن مطابقت ندارد'
        ];
    }
}
