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
            'kyc' => $this->whenLoaded('kyc', function () {
                return [
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
                ];
            }),

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

            $this->whenLoaded('personalInfo', [
                'customs' => array_merge(
                    collect([
                        'occupation',
                        'education',
                        'loved_city',
                        'loved_country',
                        'loved_language',
                        'prediction',
                        'memory',
                        'about'
                    ])->mapWithKeys(function ($field) {
                        return $this->mergeWhen($this->checkFilter($field), [
                            $field => $this->personalInfo->$field,
                        ]);
                    })->toArray(),
                    [
                        'passions' => $this->mergeWhen($this->checkFilter('passions'), [
                            'passions' => collect([
                                'music',
                                'sport_health',
                                'art',
                                'language_culture',
                                'philosophy',
                                'animals_nature',
                                'aliens',
                                'food_cooking',
                                'travel_leature',
                                'manufacturing',
                                'science_technology',
                                'space_time',
                                'history',
                                'politics_economy'
                            ])->mapWithKeys(function ($passion) {
                                return $this->mergeWhen($this->personalInfo->passions[$passion], [
                                    $passion => url("/uploads/favorites/{$passion}.png"),
                                ]);
                            })->toArray()
                        ])
                    ]
                )
            ]),

            $this->mergeWhen($this->checkFilter('score'), [
                'score' => $this->score,
            ]),

            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->latest_level, $this->score),

            $this->mergeWhen($this->latest_level && $this->checkFilter('level'), [
                'current_level' => [
                    'name' => $this->latest_level?->name,
                    'slug' => $this->latest_level?->slug,
                    'image' => config('app.admin_panel_url') . '/uploads/' . $this->latest_level?->image?->url,
                ],
                'achieved_levels' => getSubLevels($this->latest_level),
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
