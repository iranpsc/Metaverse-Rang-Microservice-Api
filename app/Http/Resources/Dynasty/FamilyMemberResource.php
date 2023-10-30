<?php

namespace App\Http\Resources\Dynasty;

use Illuminate\Http\Resources\Json\JsonResource;

class FamilyMemberResource extends JsonResource
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
            'id' => $this->user->id,
            'code' => $this->user->code,
            'profile_photo' => $this->user->profilePhotos->last()?->url,
            'online' => $this->user->isOnline(),
            'relationship' => $this->relationship,
            'level' => $this->user->level?->slug,
            $this->mergeWhen($this->user->isUnderEighteen(), [
                'permissions' => [
                    'BFR' => $this->user->permissions?->BFR,
                    'SF' => $this->user->permissions?->SF,
                    'W' => $this->user->permissions?->W,
                    'JU' => $this->user->permissions?->JU,
                    'DM' => $this->user->permissions?->DM,
                    'PIUP' => $this->user->permissions?->PIUP,
                    'PITC' => $this->user->permissions?->PITC,
                    'PIC' => $this->user->permissions?->PIC,
                    'ESOO' => $this->user->permissions?->ESOO,
                    'COTB' => $this->user->permissions?->COTB,
                ]
            ]),
        ];
    }
}
