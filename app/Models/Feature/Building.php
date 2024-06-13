<?php

namespace App\Models\Feature;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Building extends Pivot
{
    protected $fillable = [
        'model_id',
        'feature_id',
        'construction_start_date',
        'construction_end_date',
        'launched_satisfaction',
        'information',
        'rotation',
        'position',
        'bubble_diameter'
    ];

    protected $casts = [
        'information' => 'array',
        'position' => 'array',
        'construction_start_date' => 'datetime',
        'construction_end_date' => 'datetime',
        'bubble_diameter' => 'float'
    ];

    public $timestamps = true;

    /**
     * Get the model that owns the Building
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(\App\Models\Feature::class);
    }
}
