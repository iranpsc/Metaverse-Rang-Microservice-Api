<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VideoCommentResource extends JsonResource
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
            'id' => $this->id,
            'video_id' => $this->commentable->id,
            'user_id' => $this->user->id,
            'commenter_name' => $this->user->verified()
                ? implode(' ', [$this->user->kyc->fname, $this->user->kyc->lname])
                : $this->user->name,
            'commenter_code' => $this->user->code,
            'commenter_image' => $this->user->profilePhotos->last()?->url,
            'content' => $this->content,
            'likes' => $this->interactions->where('liked', 1)->count(),
            'dislikes' => $this->interactions->where('liked', 0)->count(),
            'created_at' => jdate($this->created_at)->format('Y/m/d')
        ];
    }
}
