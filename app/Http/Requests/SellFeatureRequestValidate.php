<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellFeatureRequestValidate extends FormRequest
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
            'price_psc' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => !request()->has('minimum_price_percentage')),
                Rule::prohibitedIf(fn () => request()->has('minimum_price_percentage')),
                function ($attribute, $value, $fail) {
                    if (request()->price_irr == 0 && $value == 0) {
                        $fail("{$attribute} must be greater than 0!");
                    }
                }
            ],
            'price_irr' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => !request()->has('minimum_price_percentage')),
                Rule::prohibitedIf(fn () => request()->has('minimum_price_percentage')),
                function ($attribute, $value, $fail) {
                    if (request()->price_psc == 0 && $value == 0) {
                        $fail("{$attribute} must be greater than 0!");
                    }
                }
            ],
            'minimum_price_percentage' => [
                'nullable',
                'numeric',
                'min:80',
                Rule::requiredIf(fn () => !request()->has('price_irr') && !request()->has('price_psc')),
                Rule::prohibitedIf(fn () => request()->has('price_irr') || request()->has('price_psc')),
            ],
        ];
    }
}
