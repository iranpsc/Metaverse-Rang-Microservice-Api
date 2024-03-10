<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKycRequest extends FormRequest
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
            'melli_code' => 'required|ir_national_code|unique:kycs,melli_code',
            'birthdate' => 'required|shamsi_date',
            'father_name' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'address' => 'required',
            'postal_code' => 'required|ir_postal_code',
            'number' => 'required|integer',
            'site' => 'nullable|url',
            'melli_card' => 'required|image|max:5000',
            'prove_picture' => 'required|image|max:5000',
            'resume' => 'nullable|image|max:5000',
        ];
    }
}
