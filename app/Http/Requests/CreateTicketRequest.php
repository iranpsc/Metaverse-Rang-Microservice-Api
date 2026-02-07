<?php

namespace App\Http\Requests;

use App\Enums\Departments;
use App\Rules\SecureFileUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateTicketRequest extends FormRequest
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
            'title' => 'required|string|max:250',
            'content' => 'required|string|max:500',
            'attachment' => ['nullable', 'file', new SecureFileUpload(['png', 'jpg', 'jpeg', 'pdf', 'docx', 'doc'], 5000)],
            'reciever' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::requiredIf(fn() => !request()->has('department')),
                Rule::prohibitedIf(fn() => request()->has('department'))
            ],
            'department' => [
                'nullable',
                'string',
                new Enum(Departments::class),
                Rule::requiredIf(fn() => !request()->has('reciever')),
                Rule::prohibitedIf(fn() => request()->has('reciever'))
            ],
        ];
    }
}
