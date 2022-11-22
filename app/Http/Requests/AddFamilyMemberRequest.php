<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddFamilyMemberRequest extends FormRequest
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
            'user_id' => 'required|numeric|integer|min:1',
            'relationship' => 'required|string|in:father,mother,brother,sister,offspring'
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'آیدی کاربر را وارد کنید',
            'user_id.numeric' => 'آیدی کاربر صحیح نیست',
            'user_id.integer' => 'آیدی کاربر صحیح نیست',
            'user_id.min' => 'آیدی کاربر صحیح نیست',
            'relationship.required' => 'نسبت خانوادگی را مشخص کنید',
            'relationship.in' => 'نسبت خانوادگی معتبر نیست',
            'relationship.string' => 'نسبت خانوادگی معتبر نیست',
        ];
    }
}
