<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\Level\GemResource;
use App\Http\Resources\V2\Level\GeneralInfoResource;
use App\Http\Resources\V2\Level\GiftResource;
use App\Http\Resources\V2\Level\LevelResource;
use App\Http\Resources\V2\Level\LicensesResource;
use App\Http\Resources\V2\Level\PrizeResource;
use App\Models\Levels\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function index()
    {
        $levels = Level::select('id', 'name', 'slug')->with('image')->get();
        return LevelResource::collection($levels);
    }

    public function show(Level $level)
    {
        $level->load('image', 'generalInfo');
        return new LevelResource($level);
    }

    public function getGeneralInfo(Level $level)
    {
        return new GeneralInfoResource($level->generalInfo);
    }

    public function gift(Level $level)
    {
        return new GiftResource($level->gift);
    }

    public function gem(Level $level)
    {
        return new GemResource($level->gem);
    }

    public function licenses(Level $level)
    {
        return new LicensesResource($level->licenses);
    }

    public function prizes(Level $level)
    {
        return new PrizeResource($level->prize);
    }
}
