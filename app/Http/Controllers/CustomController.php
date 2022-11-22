<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCustomRequest;
use App\Http\Resources\CustomsResource;
use App\Models\User;
use App\Models\User\Custom;
use App\Models\User\Passion;
use Illuminate\Http\Request;

class CustomController extends Controller
{

        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new CustomsResource(auth()->user()->customs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateCustomRequest $request)
    {
        $this->authorize('addCustom', Custom::class);
        $custom = Custom::create([
            'user_id' => $request->user()->id,
            'occupation' => $request->occupation,
            'education' => $request->education,
            'memory' => $request->memory,
            'loved_city' => $request->loved_city,
            'loved_country' => $request->loved_country,
            'loved_language' => $request->loved_language,
            'problem_solving' => $request->problem_solving,
            'prediction' => $request->prediction,
            'about' => $request->about,
        ]);

        if($request->has('passions')) {
            $passions = Passion::create([
                'custom_id' => $custom->id,
                'music' => $request->passions['music'],
                'sport_health' => $request->passions['sport_health'],
                'art' => $request->passions['art'],
                'language_culture' => $request->passions['language_culture'],
                'philosophy' => $request->passions['philosophy'],
                'animals_nature' => $request->passions['animals_nature'],
                'aliens' => $request->passions['aliens'],
                'food_cooking' => $request->passions['food_cooking'],
                'travel_leature' => $request->passions['travel_leature'],
                'manufacturing' => $request->passions['manufacturing'],
                'science_technology' => $request->passions['science_technology'],
                'space_time' => $request->passions['space_time'],
                'history' => $request->passions['history'],
                'politics_economy' => $request->passions['politics_economy']
            ]);
        }

        return new CustomsResource($custom);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Custom $custom)
    {
        $this->authorize('updateCustom', $custom);
        $custom->update([
            'user_id' => $request->user()->id,
            'profile_code' => $request->profile_code,
            'occupation' => $request->occupation,
            'education' => $request->education,
            'memory' => $request->memory,
            'loved_city' => $request->loved_city,
            'loved_country' => $request->loved_country,
            'loved_language' => $request->loved_language,
            'problem_solving' => $request->problem_solving,
            'prediction' => $request->prediction,
            'about' => $request->about,
        ]);

        if($request->has('passions')) {
            $passions = $custom->passions->update([
                'custom_id' => $custom->id,
                'music' => $request->passions['music'],
                'sport_health' => $request->passions['sport_health'],
                'art' => $request->passions['art'],
                'language_culture' => $request->passions['language_culture'],
                'philosophy' => $request->passions['philosophy'],
                'animals_nature' => $request->passions['animals_nature'],
                'aliens' => $request->passions['aliens'],
                'food_cooking' => $request->passions['food_cooking'],
                'travel_leature' => $request->passions['travel_leature'],
                'manufacturing' => $request->passions['manufacturing'],
                'science_technology' => $request->passions['science_technology'],
                'space_time' => $request->passions['space_time'],
                'history' => $request->passions['history'],
                'politics_economy' => $request->passions['politics_economy']
            ]);
        }
        return new CustomsResource($custom);

    }
}
