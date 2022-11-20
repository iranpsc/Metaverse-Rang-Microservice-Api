<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomRequest extends FormRequest
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
            'occupation' => 'nullable|string',
            'education' =>  'nullable|string',
            'memory' => 'nullable|string',
            'loved_city' => 'nullable|string',
            'loved_county' => 'nullable|string',
            'loved_languege' => 'nullable|string',
            'problem_solving' => 'nullable|string',
            'prediction' => 'nullable|string',
            'about' => 'required|string'
        ];
    }

    public function messages(){
        return [
            'occupation.string' => 'شغل کاربر تحصیل صحیح نمی باشد',
            'education.string'=>'تحصیلات صحیح نمی باشد',
            'memory.string' => 'خاطرات صحیح نمی باشد ',
            'loved_city.string' => 'شهری که وارد کردید صحیح نیست ',
            'loved_county.string' => 'کشوری که وارد کردید صحیح نیست',
            'loved_languege.string' => 'زبانی که وارد کردید صحیح نیست ',
            'problem_solving.string' => 'متن فرصتی  برای  حل مشکل صحیح نیست',
            'prediction.string' => 'متن  پیش بینی صحیح نمی باشد',
            'about.required' => 'متن درباره خود را وارد کنید'
        ];
    }
}
