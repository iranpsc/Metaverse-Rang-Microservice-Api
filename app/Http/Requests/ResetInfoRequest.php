<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Reset;
use Illuminate\Validation\Rule;

class ResetInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = request()->user();
        $type = request()->has('phone') ? 'phone' : 'email';
        return Reset::resetInfo($user, $type)->count() >= 1 || !in_array($type, ['email', 'phone'])
            ? abort(401, sprintf('تعداد دفعات تغییر %s 1 بار می باشد!', $type == 'phone' ? 'تلفن همراه' : 'ایمیل')) : true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'phone' =>
            Rule::when(request()->has('phone'), [
                'required',
                'ir_mobile',
                'unique:users,phone'
            ]),
            'email' =>
            Rule::when(request()->has('email'), [
                'required',
                'email',
                'unique:users,email'
            ]),
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'شماره تلفن همراه خود را وارد کنید!',
            'phone.unique' => 'این شماره تلفن قبلا استفاده شده است!',
            'phone.ir_mobile' => 'شماره تلفن صحیح نمی باشد!',
            'email.required' => 'ایمیل را وارد کنید',
            'email.unique' => 'این ایمیل قبلا استفاده شده است!',
            'email.email' => 'ایمیل صحیح نمی باشد!',
        ];
    }
}
