<?php

namespace App\Http\Resources\PublicProfile;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
            $this->mergeWhen($this->profilePhotos, [
                'profilePhotos' => $this->profilePhotos,
            ]),
            'kyc' => [
                $this->mergeWhen($this->verified(), [
                    'nationality' => 'https://dl.qzparadise.ir/public/flags/iran.png',
                    'fname' => $this->kyc?->fname,
                    'lname' => $this->kyc?->lname,
                    'birth_date' => Jalalian::forge($this->kyc?->birthdate)->format('Y/m/d'),
                    'phone' => $this?->phone,
                    'email' => $this?->email,
                    'address' => $this->kyc?->address,
                ]),
            ],

            'code' => $this->code,
            'name' => $this->name,
            'position' => 'مدیریت موازی',
            'registered_at' => Jalalian::forge($this->email_verified_at)->format('Y/m/d'),
            $this->mergeWhen($this->customs, [
                'customs' => [
                    'occupation' => $this->customs?->occupation,
                    'education' => $this->customs?->education,
                    'loved_city' => $this->customs?->loved_city,
                    'loved_country' => $this->customs?->loved_country,
                    'loved_language' => $this->customs?->loved_language,
                    'prediction' => $this->customs?->prediction,
                    'memory' => $this->customs?->memory,
                    'about' => $this->customs?->about,
                    $this->mergeWhen($this->customs?->passions, [
                        'passions' => [
                            $this->mergeWhen($this->customs?->passions?->music, [
                                "music"=>  'https://dl.qzparadise.ir/public/customs/music.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->sport_health , [

                                "sport_health" => 'https://dl.qzparadise.ir/public/customs/sport_health.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->art, [

                                "art" =>   'https://dl.qzparadise.ir/public/customs/art.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->language_culture, [

                                "language_culture" =>'https://dl.qzparadise.ir/public/customs/language_culture.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->philosophy, [

                                "philosophy" =>  'https://dl.qzparadise.ir/public/customs/philosophy.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->animals_nature, [

                                "animals_nature" =>  'https://dl.qzparadise.ir/public/customs/animals_nature.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->aliens,[

                                "aliens" =>  'https://dl.qzparadise.ir/public/customs/aliens.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->food_cooking, [

                                "food_cooking" =>  'https://dl.qzparadise.ir/public/customs/food_cooking.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->travel_leature, [

                                "travel_leature" => 'https://dl.qzparadise.ir/public/customs/travel_leature.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->manufacturing, [

                                "manufacturing" =>  'https://dl.qzparadise.ir/public/customs/manufacturing.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->science_technology, [

                                "science_technology" => 'https://dl.qzparadise.ir/public/customs/science_technology.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->space_time, [

                                "space_time"  =>  'https://dl.qzparadise.ir/public/customs/space_time.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->history, [

                                "history" =>  'https://dl.qzparadise.ir/public/customs/history',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->politics_economy, [

                                "politics_economy" =>  'https://dl.qzparadise.ir/public/customs/politics_economy',
                            ])
                        ]
                    ]),
                ]
            ]),
            'score' => $this->score,
            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->level, $this->score),
            $this->mergeWhen($this->level, [
                'level' => [
                    'name' => $this->level?->name,
                    'slug' => $this->level?->slug,
                    'levels_images' => [
                        'images' => getLevelsImages($this->level),
                    ]
                ]
            ]),
            'avatar' => 'https://irpsc.com/gb.glb',
        ];
    }
}
