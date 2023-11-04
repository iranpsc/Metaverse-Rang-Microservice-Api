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
                'profilePhotos' => $this->profilePhotos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url,
                    ];
                }),
            ]),
            'kyc' => [
                $this->mergeWhen($this->verified(), [
                    $this->mergeWhen($this->privacy->where('name', 'nationality')->pluck('display')->first(), [
                        'nationality' => config('app.url') . '/uploads/flags/iran.svg',
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'fname')->pluck('display')->first(), [
                        'fname' => $this->kyc?->fname,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'lname')->pluck('display')->first(), [
                        'lname' => $this->kyc?->lname,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'birthdate')->pluck('display')->first(), [

                        'birth_date' => Jalalian::forge($this->kyc?->birthdate)->format('Y/m/d'),
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'phone')->pluck('display')->first(), [
                        'phone' => $this?->phone,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'email')->pluck('display')->first(), [
                        'email' => $this?->email,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'address')->pluck('display')->first(), [
                        'address' => $this->kyc?->address,
                    ]),


                ]),
            ],

            $this->mergeWhen($this->privacy->where('name', 'code')->pluck('display')->first(), [
                'code' => $this->code,
            ]),

            $this->mergeWhen($this->privacy->where('name', 'name')->pluck('display')->first(), [
                'name' => $this->name,
            ]),

            $this->mergeWhen($this->privacy->where('name', 'position')->pluck('display')->first(), [
                'position' => 'مدیریت موازی',
            ]),

            $this->mergeWhen($this->privacy->where('name', 'registered_at')->pluck('display')->first(), [
                'registered_at' => Jalalian::forge($this->email_verified_at)->format('Y/m/d'),
            ]),

            $this->mergeWhen($this->customs, [
                'customs' => [

                    $this->mergeWhen($this->privacy->where('name', 'occupation')->pluck('display')->first(), [
                        'occupation' => $this->customs?->occupation,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'education')->pluck('display')->first(), [
                        'education' => $this->customs?->education,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'loved_city')->pluck('display')->first(), [
                        'loved_city' => $this->customs?->loved_city,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'loved_country')->pluck('display')->first(), [
                        'loved_country' => $this->customs?->loved_country,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'loved_language')->pluck('display')->first(), [
                        'loved_language' => $this->customs?->loved_language,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'prediction')->pluck('display')->first(), [
                        'prediction' => $this->customs?->prediction,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'memory')->pluck('display')->first(), [
                        'memory' => $this->customs?->memory,
                    ]),

                    $this->mergeWhen($this->privacy->where('name', 'about')->pluck('display')->first(), [
                        'about' => $this->customs?->about,
                    ]),

                    'about' => $this->customs?->about,
                    $this->mergeWhen($this->customs?->passions && $this->privacy->where('name', 'passions')->pluck('display')->first(), [
                        'passions' => [
                            $this->mergeWhen($this->customs?->passions?->music, [
                                "music" =>  'https://dl.qzparadise.ir/public/customs/music.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->sport_health, [

                                "sport_health" => 'https://dl.qzparadise.ir/public/customs/sport_health.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->art, [

                                "art" =>   'https://dl.qzparadise.ir/public/customs/art.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->language_culture, [

                                "language_culture" => 'https://dl.qzparadise.ir/public/customs/language_culture.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->philosophy, [

                                "philosophy" =>  'https://dl.qzparadise.ir/public/customs/philosophy.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->animals_nature, [

                                "animals_nature" =>  'https://dl.qzparadise.ir/public/customs/animals_nature.png',
                            ]),
                            $this->mergeWhen($this->customs?->passions?->aliens, [

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

                                "politics_economy" =>  'https://dl.qzparadise.ir/public/customs/politics_economy.png',
                            ])
                        ]
                    ]),
                ]
            ]),

            $this->mergeWhen($this->privacy->where('name', 'score')->pluck('display')->first(), [
                'score' => $this->score,
            ]),


            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->level, $this->score),
            $this->mergeWhen($this->level && $this->privacy->where('name', 'level')->pluck('display')->first(), [
                'current_level' => [
                    'name' => $this->level?->name,
                    'slug' => $this->level?->slug,
                    'image' => config('app.admin_panel_url') . '/uploads/' . $this->level?->image?->url,
                ],
                'achieved_levels' => getSubLevels($this->level),
            ]),

            $this->mergeWhen($this->privacy->where('name', 'avatar')->pluck('display')->first(), [
                'avatar' => 'https://irpsc.com/gb.glb',
            ]),
        ];
    }
}
