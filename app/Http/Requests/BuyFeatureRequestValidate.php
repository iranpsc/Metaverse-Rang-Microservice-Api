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
            'note' => 'nullable',
            'price_psc' => 'required|numeric|min:0|max:100000000',
            'price_irr' => 'required|numeric|min:0|max:10000000000',
        ];
    }

    public function messages()
    {
        return [
            'price_psc.required' => 'کمترین مقدار 0 می باشد',
            'price_irr.required' => 'کمترین مقدار 0 می باشد',
            'price_psc.min' => 'کمترین مقدار 0 میباشد',
            'price_psc.max' => 'بیشترین مقدار 10000000000 می باشد',
            'price_irr.min' => 'کمترین مقدار 0 میباشد',
            'price_irr.max' => 'بیشترین مقدار 10000000000 می باشد',
        ];
    }
}
