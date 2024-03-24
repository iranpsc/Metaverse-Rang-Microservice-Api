<?php

namespace App\Http\Resources\PublicProfile;

use App\Http\Resources\ProfilePhotoResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalInfo extends JsonResource
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
            'profilePhotos' => ProfilePhotoResource::collection($this->whenLoaded('profilePhotos')),
            'kyc' => $this->whenLoaded('kyc', [
                $this->mergeWhen($this->checkFilter('nationality'), [
                    'nationality' => url('/uploads/flags/iran.svg'),
                ]),

                $this->mergeWhen($this->checkFilter('fname'), [
                    'fname' => $this->kyc?->fname,
                ]),

                $this->mergeWhen($this->checkFilter('lname'), [
                    'lname' => $this->kyc?->lname,
                ]),

                $this->mergeWhen($this->checkFilter('birthdate'), [
                    'birth_date' => jdate($this->kyc?->birthdate)->format('Y/m/d'),
                ]),

                $this->mergeWhen($this->checkFilter('phone'), [
                    'phone' => $this?->phone,
                ]),

                $this->mergeWhen($this->checkFilter('email'), [
                    'email' => $this?->email,
                ]),

                $this->mergeWhen($this->checkFilter('address'), [
                    'address' => $this->kyc?->address,
                ]),
            ]),

            $this->mergeWhen($this->checkFilter('code'), [
                'code' => $this->code,
            ]),

            $this->mergeWhen($this->checkFilter('name'), [
                'name' => $this->name,
            ]),

            $this->mergeWhen($this->checkFilter('position'), [
                'position' => 'مدیریت موازی',
            ]),

            $this->mergeWhen($this->checkFilter('registered_at'), [
                'registered_at' => jdate($this->email_verified_at)->format('Y/m/d'),
            ]),

            $this->mergeWhen($this->customs, [
                'customs' => [
                    $this->mergeWhen($this->checkFilter('occupation'), [
                        'occupation' => $this->customs?->occupation,
                    ]),

                    $this->mergeWhen($this->checkFilter('education'), [
                        'education' => $this->customs?->education,
                    ]),

                    $this->mergeWhen($this->checkFilter('loved_city'), [
                        'loved_city' => $this->customs?->loved_city,
                    ]),

                    $this->mergeWhen($this->checkFilter('loved_country'), [
                        'loved_country' => $this->customs?->loved_country,
                    ]),

                    $this->mergeWhen($this->checkFilter('loved_language'), [
                        'loved_language' => $this->customs?->loved_language,
                    ]),

                    $this->mergeWhen($this->checkFilter('prediction'), [
                        'prediction' => $this->customs?->prediction,
                    ]),

                    $this->mergeWhen($this->checkFilter('memory'), [
                        'memory' => $this->customs?->memory,
                    ]),

                    $this->mergeWhen($this->checkFilter('about'), [
                        'about' => $this->customs?->about,
                    ]),

                    $this->mergeWhen($this->customs?->passions && $this->checkFilter('passions'), [
                        'passions' => [
                            $this->mergeWhen($this->customs?->passions?->music, [
                                "music" => url('/uploads/favorites/music.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->sport_health, [
                                "sport_health" => url('/uploads/favorites/sport_health.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->art, [
                                "art" => url('/uploads/favorites/art.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->language_culture, [
                                "language_culture" => url('/uploads/favorites/language_culture.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->philosophy, [
                                "philosophy" => url('/uploads/favorites/philosophy.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->animals_nature, [
                                "animals_nature" => url('/uploads/favorites/animals_nature.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->aliens, [
                                "aliens" => url('/uploads/favorites/aliens.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->food_cooking, [
                                "food_cooking" => url('/uploads/favorites/food_cooking.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->travel_leature, [
                                "travel_leature" => url('/uploads/favorites/travel_leature.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->manufacturing, [
                                "manufacturing" => url('/uploads/favorites/manufacturing.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->science_technology, [
                                "science_technology" => url('/uploads/favorites/science_technology.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->space_time, [
                                "space_time" => url('/uploads/favorites/space_time.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->history, [
                                "history" => url('/uploads/favorites/history.png'),
                            ]),
                            $this->mergeWhen($this->customs?->passions?->politics_economy, [
                                "politics_economy" => url('/uploads/favorites/politics_economy.png'),
                            ])
                        ]
                    ]),
                ]
            ]),

            $this->mergeWhen($this->checkFilter('score'), [
                'score' => $this->score,
            ]),

            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->level, $this->score),

            $this->mergeWhen($this->level && $this->checkFilter('level'), [
                'current_level' => [
                    'name' => $this->level?->name,
                    'slug' => $this->level?->slug,
                    'image' => config('app.admin_panel_url') . '/uploads/' . $this->level?->image?->url,
                ],
                'achieved_levels' => getSubLevels($this->level),
            ]),

            $this->mergeWhen($this->checkFilter('avatar'), [
                'avatar' => 'https://irpsc.com/gb.glb',
            ]),
        ];
    }

    /**
     * Check the filter
     *
     * @param string $name
     * @return bool
     */
    private function checkFilter(string $name)
    {
        return $this->privacy->where('name', $name)->pluck('display')->first();
    }
}
