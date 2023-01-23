<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;
use App\Helpers\AssetHelper;

class TransactionResource extends JsonResource
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
            'created_at' => Jalalian::forge($this->created_at)->format('Y/m/d H:m:s'),
            'type'   => getTransactionTitle($this->resource),
            'asset'  => AssetHelper::getAssetTitle($this->asset),
            'amount' => $this->amount,
            'action' => $this->action === 'withdraw' ? 'برداشت' : 'واریز',
            'status' => getTransactionStatus($this->resource)
        ];
    }
}
