<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'occupation' => $this->occupation,
            'education' => $this->education,
            'memory' => $this->memory,
            'loved_city' => $this->loved_city,
            'loved_country' => $this->loved_country,
            'loved_language' => $this->loved_language,
            'problem_solving' => $this->problem_solving,
            'prediction' => $this->prediction,
            'about' => $this->about,
            'passions' => [
                'music' => $this->passions->music,
                'sport_health' => $this->passions->sport_health,
                'art' => $this->passions->art,
                'language_culture' => $this->passions->language_culture,
                'philosophy' => $this->passions->philosophy,
                'animals_nature' => $this->passions->animals_nature,
                'aliens' => $this->passions->aliens,
                'food_cooking' => $this->passions->food_cooking,
                'travel_leature' => $this->passions->travel_leature,
                'manufacturing' => $this->passions->manufacturing,
                'science_technology' => $this->passions->science_technology,
                'space_time' => $this->passions->space_time,
                'history' => $this->passions->history,
                'politics_economy' => $this->passions->politics_economy,
            ],
        ];
    }
}
