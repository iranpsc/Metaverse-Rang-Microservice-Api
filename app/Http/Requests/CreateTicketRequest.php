<?php

namespace App\Http\Requests;

use App\Enums\Departments;
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
            'attachment' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:1024',
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
