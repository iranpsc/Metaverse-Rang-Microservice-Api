<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $authUser = $request->user();
        $isAuthUser = $authUser && $authUser->id === $this->id;
        $isFollowing = $authUser && $authUser->following()->where('following_id', $this->id)->exists();
        $isFollower = $authUser && $authUser->followers()->where('id', $this->id)->exists();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'profile_photos' => $this->latestProfilePhoto?->url ?? [],
            'level' => $this->latestLevel?->slug ?? '',
            'online' => $this->isOnline(),
            'can' => [
                'follow' => $authUser && !$isAuthUser && !$isFollowing,
                'unfollow' => $isFollowing,
                'remove_follower' => $isFollower,
            ],
        ];
    }
}
