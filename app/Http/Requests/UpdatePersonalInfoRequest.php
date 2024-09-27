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
            'occupation' => 'required|string|max:255',
            'education' => 'required|string|max:255',
            'memory' => 'required|string|max:2000',
            'loved_city' => 'required|string|max:255',
            'loved_country' => 'required|string|max:255',
            'loved_language' => 'required|string|max:255',
            'problem_solving' => 'required|string|max:2000',
            'prediction' => 'required|string|max:10000',
            'about' => 'required|string|max:10000',
            'passions' => 'required|array',
            'passions.music' => 'required|boolean',
            'passions.sport_health' => 'required|boolean',
            'passions.art' => 'required|boolean',
            'passions.language_culture' => 'required|boolean',
            'passions.philosophy' => 'required|boolean',
            'passions.animals_nature' => 'required|boolean',
            'passions.aliens' => 'required|boolean',
            'passions.food_cooking' => 'required|boolean',
            'passions.travel_leature' => 'required|boolean',
            'passions.manufacturing' => 'required|boolean',
            'passions.science_technology' => 'required|boolean',
            'passions.space_time' => 'required|boolean',
            'passions.history' => 'required|boolean',
        ];
    }
}
