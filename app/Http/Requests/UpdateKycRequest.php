<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKycRequest extends FormRequest
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
            'fname' => 'required|string|min:2',
            'lname' => 'required|string|min:2',
            'melli_code' => 'required|ir_national_code|unique:kycs,melli_code,' . $this->route('kyc')->id . ',id',
            'birthdate' => 'required|shamsi_date',
            'province' => 'required|string',
            'melli_card' => 'nullable|image|max:1024',
            'video' => 'required|array',
            'verify_text' => 'required|string|max:5000',
        ];
    }
}
