<?php

namespace App\Models\Feature;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BuildingModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'name',
        'sku',
        'images',
        'attributes',
        'file',
        'required_satisfaction',
    ];

    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'file' => 'array',
    ];

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'building', 'model_id', 'feature_id');
    }

}
