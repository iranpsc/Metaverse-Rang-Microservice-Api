<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuyFeatureRequestValidate extends FormRequest
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
            'note' => 'nullable|string|max:500',
            'price_psc' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if (request()->price_irr == 0 && $value == 0) {
                        $fail("{$attribute} must be greater than 0!");
                    }
                }
            ],
            'price_irr' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if (request()->price_psc == 0 && $value == 0) {
                        $fail("{$attribute} must be greater than 0!");
                    }
                }
            ],
        ];
    }
}
