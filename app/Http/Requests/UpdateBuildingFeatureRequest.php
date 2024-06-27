<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingFeatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->route('feature')->owner->is($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'activity_line' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|ir_postal_code',
            'website' => 'nullable|active_url|max:255',
            'description' => 'nullable|string|max:5000',
            'launched_satisfaction' => [
                'required',
                'numeric',
                'min:' . $this->route('buildingModel')->required_satisfaction,
                'max:' . $this->user()->wallet->satisfaction,
            ],
            'rotation' => 'required|numeric',
            'position' => [
                'required',
                'regex:/^(-?\d+(\.\d+)?),\s*(-?\d+(\.\d+)?)$/'
            ],
        ];
    }
}
