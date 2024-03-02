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
        'position'
    ];

    protected $casts = [
        'information' => 'array',
        'position' => 'array',
        'construction_start_date' => 'datetime',
        'construction_end_date' => 'datetime',
    ];

    public $timestamps = true;
}
