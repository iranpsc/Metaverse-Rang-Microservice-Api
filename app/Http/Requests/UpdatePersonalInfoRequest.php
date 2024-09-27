<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = $this->user();
        $personalInfo = $user->personalInfo;

        return $personalInfo === null || ($personalInfo->user->is($user) && $user->personalInfo->updated_at < now()->subMonth());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'occupation' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'memory' => 'nullable|string|max:2000',
            'loved_city' => 'nullable|string|max:255',
            'loved_country' => 'nullable|string|max:255',
            'loved_language' => 'nullable|string|max:255',
            'problem_solving' => 'nullable|string|max:2000',
            'prediction' => 'nullable|string|max:10000',
            'about' => 'nullable|string|max:10000',
            'passions' => 'nullable|array',
            'passions.music' => 'nullable|boolean',
            'passions.sport_health' => 'nullable|boolean',
            'passions.art' => 'nullable|boolean',
            'passions.language_culture' => 'nullable|boolean',
            'passions.philosophy' => 'nullable|boolean',
            'passions.animals_nature' => 'nullable|boolean',
            'passions.aliens' => 'nullable|boolean',
            'passions.food_cooking' => 'nullable|boolean',
            'passions.travel_leature' => 'nullable|boolean',
            'passions.manufacturing' => 'nullable|boolean',
            'passions.science_technology' => 'nullable|boolean',
            'passions.space_time' => 'nullable|boolean',
            'passions.history' => 'nullable|boolean',
        ];
    }
}
