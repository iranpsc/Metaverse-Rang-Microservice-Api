<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
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
            'title' => 'required|string|max:130',
            'content' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:5000'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'عنوان یادداشت را وارد کنید',
            'content.required' => 'متن یادداشت را وارد کنید',
        ];
    }
}
