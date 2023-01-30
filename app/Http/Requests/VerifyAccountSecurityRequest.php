<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class VerifyAccountSecurityRequest extends FormRequest
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
            'code' => 'required|integer|min:100000|max:999999',
        ];
    }

    public function messages()
    {
       return [
            'code.required' => 'کد تایید را وارد کنید',
            'code.integer' => 'کد تایید صحیح نیست',
            'code.min' => 'کد تایید صحیح نیست',
            'code.max' => 'کد تایید صحیح نیست'
       ];
    }
}
