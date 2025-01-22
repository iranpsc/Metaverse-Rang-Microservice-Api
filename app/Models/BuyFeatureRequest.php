<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyFeatureRequest extends Model
{
    use SoftDeletes;

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
        'note',
        'price_psc',
        'price_irr',
        'requested_grace_period',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected  function casts()
    {
        return [
            'seller_id' => 'int',
            'feature_id' => 'int',
            'buyer_id' => 'int',
            'status' => 'int',
            'requested_grace_period' => 'timestamp',
        ];
    }

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 0,
        'requested_grace_period' => null,
    ];

    /**
     * Get the seller that owns the BuyFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public  function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the buyer that owns the BuyFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the feature that owns the BuyFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    /**
     * Get the locked asset that owns the BuyFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lockedAsset()
    {
        return $this->hasOne(LockedAsset::class);
    }

    /**
     * Get the transactions for the BuyFeatureRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'payable');
    }
}
