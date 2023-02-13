<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellFeatureRequest extends Model
{
    use HasFactory;

    protected $casts = [
        'seller_id' => 'int',
        'feature_id' => 'int',
        'buyer_id' => 'int'
    ];

    protected $fillable = [
        'seller_id',
        'buyer_id',
        'feature_id',
        'status',
        'price_psc',
        'price_irr',
        'limit',
        'minimum_price_percentage'
    ];

    protected $attributes = [
        'status' => 0,
    ];

    public  function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    public function scopeLatestUnderPriceRequests($query, User $user, Feature $feature)
    {
        return $query->where('seller_id', $user->id)
            ->where('limit', '<', 100)
            ->where('status', 1)
            ->whereNot('feature_id', $feature->id)
            ->get();
    }
}
