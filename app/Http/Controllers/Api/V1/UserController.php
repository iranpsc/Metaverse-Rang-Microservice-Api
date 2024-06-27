<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Http\Resources\WalletResource;
use App\Models\Level\Level;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        //
    }

    /**
     * Get the list of top users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(['data' => $this->userRepository->topUsers()]);
    }

    /**
     * Get the wallet resource for a specific user.
     *
     * @param User $user The user object.
     * @return WalletResource
     */
    public function getWallet(User $user)
    {
        return new WalletResource($user->wallet);
    }

    /**
     * Get the count of features for a specific user.
     *
     * @param User $user The user object.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeaturesCount(User $user)
    {
        $user->loadCount([
            'features as maskoni_features_count' => function ($query) {
                $query->whereHas('properties', function ($query) {
                    $query->where('karbari', 'm');
                });
            },
            'features as tejari_features_count' => function ($query) {
                $query->whereHas('properties', function ($query) {
                    $query->where('karbari', 't');
                });
            },
            'features as amoozeshi_features_count' => function ($query) {
                $query->whereHas('properties', function ($query) {
                    $query->where('karbari', 'a');
                });
            }
        ]);

        return response()->json([
            'data' => [
                'maskoni_features' => $user->maskoni_features_count,
                'tejari_features' => $user->tejari_features_count,
                'amoozeshi_features' => $user->amoozeshi_features_count
            ]
        ]);
    }

    /**
     * Get the profile of a user.
     *
     * @param User $user The user object.
     * @return ProfileResource The profile resource.
     */
    public function getProfile(User $user)
    {
        $user->load('profilePhotos')->loadCount(['followers', 'following']);
        return new ProfileResource($user);
    }

    /**
     * Get the level of a user.
     *
     * @param User $user The user object.
     * @return JsonResponse The JSON response containing the user's level information.
     */
    public function getLevel(User $user)
    {
        $user->load('level.image');

        if (is_null($user->level)) {
            return response()->json([
                'data' => [
                    'current_level' => null,
                    'previous_levels' => []
                ]
            ]);
        }

        $previousLevels = Level::where('score', '<', $user->level->score)->orderBy('score')->get();
        $currentLevel = $user->level;

        return response()->json([
            'data' => [
                'current_level' => [
                    'id' => $currentLevel->id,
                    'name' => $currentLevel->name,
                    'score' => $currentLevel->score,
                    'slug' => $currentLevel->slug,
                    'image' => url('uploads/' . optional($currentLevel->image)->url)
                ],
                'previous_levels' => $previousLevels->map(function ($level) {
                    return [
                        'id' => $level->id,
                        'name' => $level->name,
                        'score' => $level->score,
                        'slug' => $level->slug,
                        'image' => url('uploads/' . optional($level->image)->url)
                    ];
                }),
                'remaining_score_percentage_to_next_level' => $currentLevel->getScorePercentageToNextLevel($user),
            ]
        ]);
    }
}
