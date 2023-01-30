<?php

namespace App\Http\Requests;

use App\Enums\GeneralSettingFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateGeneralSettingsRequest extends FormRequest
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
            'setting' => [
                'required',
                new Enum(GeneralSettingFields::class)
            ],
            'status' => 'required|boolean'
        ];
    }
}
