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
                'unique:users,phone',
                Rule::requiredIf(is_null(request()->user()->phone)
                || is_null(request()->user()->phone_verified_at)),
            ],
            'time' => 'required|numeric|integer|between:5,60',
        ];
    }
}
