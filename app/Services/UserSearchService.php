<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service class for handling user search operations
 */
class UserSearchService
{
    /**
     * Search for users based on a search term
     */
    public function searchUsers(string $searchTerm): Collection
    {
        return User::select(['id', 'code', 'name', 'score'])
            ->where('name', 'like', $searchTerm)
            ->orWhere('code', 'like', $searchTerm)
            ->orWhereHas('kyc', function ($query) use ($searchTerm) {
                $query->where('fname', 'like', $searchTerm)
                    ->orWhere('lname', 'like', $searchTerm);
            })
            ->with(['kyc', 'levels' => function ($query) {
                $query->orderBy('id', 'desc')->with('gem');
            }, 'latestProfilePhoto'])
            ->get();
    }

    /**
     * Format level data for a collection of levels
     */
    private function formatLevelData(Collection $levels): array
    {
        return $levels->map(function ($level) {
            return [
                'id' => $level->id,
                'name' => $level->name,
                'score' => $level->score,
                'slug' => $level->slug,
                'image' => $level->image->url,
                'gem' => [
                    'id' => $level->gem->id,
                    'name' => $level->gem->name,
                    'image' => $level->gem->png_file,
                ],
            ];
        })->toArray();
    }

    /**
     * Transform user data into a formatted array
     */
    public function transformUserData(User $user): array
    {
        $levels = $user->levels()->with('gem')->get();

        return [
            'id' => $user->id,
            'code' => $user->code,
            'score' => $user->score,
            'name' => $user->verified() ? $user->kyc->full_name : $user->name,
            'image' => $user->latestProfilePhoto?->url,
            'verified' => $user->verified(),
            'age' => $user->verified() ? (int)$user->kyc->birthdate->diffInYears(now()) : null,
            'levels' => $this->formatLevelData($levels),
        ];
    }
}
