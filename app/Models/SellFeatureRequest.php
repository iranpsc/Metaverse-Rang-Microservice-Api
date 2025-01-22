<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellFeatureRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seller_id' => 'int',
            'feature_id' => 'int',
            'buyer_id' => 'int',
            'status' => 'int',
        ];
    }

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 0,
    ];

    /**
     * Get the seller that owns the SellFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public  function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the buyer that owns the SellFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the feature that owns the SellFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    /**
     * Scope a query to only include latest under price requests.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User $user
     * @param Feature $feature
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestUnderPriceRequests($query, User $user, Feature $feature)
    {
        return $query->where('seller_id', $user->id)
            ->where('limit', '<', 100)
            ->where('status', 1)
            ->whereNot('feature_id', $feature->id)
            ->get();
    }
}
