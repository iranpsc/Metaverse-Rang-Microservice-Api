<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureProperties extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, string>
     */
    protected $fillable = [
        'id',
        'feature_id',
        'name',
        'owner',
        'address',
        'density',
        'date',
        'stability',
        'label',
        'price',
        'region',
        'area',
        'karbari',
        'status',
        'rgb',
        'price_psc',
        'price_irr',
        'minimum_price_percentage',
        'center',
    ];

    /**
     * Determine if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The key type of the model.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected function casts()
    {
        return [
            'feature_id' => 'int',
            'karbari' => 'string',
            'id' => 'string',
            'center' => 'array',
        ];
    }

    /**
     * The feature that belong to the properties.
     *
     * @return BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }
}
