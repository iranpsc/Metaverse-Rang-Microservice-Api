<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketResponseRequest extends FormRequest
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
            'response' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:png,jpg,pdf|max:5000'
        ];
    }

    public function messages()
    {
        return [
            'response.required' => 'متن پاسخ را وارد کنید',
            'response.string' => 'متن پاسخ باید متن باشد',
            'attachment.mimes' => 'فایل ضمیمه باید تصویر یا pdf باشد'
        ];
    }
}
