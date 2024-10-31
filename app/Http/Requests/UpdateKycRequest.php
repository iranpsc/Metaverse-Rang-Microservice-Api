<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKycRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $kyc = $this->user()->kyc;

        if ($kyc) {
            return $this->user()->can('update', $kyc);
        }

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
            'fname' => 'required|string|min:2|max:255',
            'lname' => 'required|string|min:2|max:255',
            'melli_code' => [
                'required',
                'ir_national_code',
                Rule::unique('kycs', 'melli_code')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                })->ignore($this->user()->kyc),
            ],
            'birthdate' => 'required|shamsi_date',
            'province' => 'required|string|max:255',
            'melli_card' => 'required|image|max:5000',
            'video' => 'required|array',
            'verify_text_id' => 'required|integer|exists:kyc_verify_texts,id',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'birthdate' => jalali_to_carbon($this->birthdate)->format('Y-m-d'),
            'status' => 0,
            'errors' => null,
        ]);
    }
}
