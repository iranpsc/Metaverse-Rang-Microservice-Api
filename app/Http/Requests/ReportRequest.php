<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
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
            'subject' => 'required|string|in:displayError,spellingError,codingError,FPSError,disrespect',
            'title' => 'required|string|max:130',
            'content' => 'required|string|max:500',
            'url'     => 'required|active_url',
            'attachment' => 'nullable|file|mimes:png,jpg,pdf,jpeg|max:1024'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'عنوان گزارش را وارد کنید',
            'content.required' => 'متن گزارش را وارد کنید'
        ];
    }
}
