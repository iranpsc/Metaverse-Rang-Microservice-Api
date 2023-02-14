<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopPlayerResource;
use App\Models\User;
use App\Repositories\UserRepository;

class PlayerController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    )
    {

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(['data' => $this->userRepository->getTopPlayers()]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $player)
    {
        return new TopPlayerResource($player);
    }
}
