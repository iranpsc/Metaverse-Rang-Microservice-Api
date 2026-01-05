<?php

namespace App\Models\Feature;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BuildingModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $fillable = [
        'model_id',
        'name',
        'sku',
        'images',
        'attributes',
        'file',
        'required_satisfaction',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'attributes' => 'array',
            'file' => 'array',
        ];
    }

    /**
     * The features that belong to the building model.
     *
     * @return BelongsToMany
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'building', 'model_id', 'feature_id');
    }

    /**
     * Get the three dimentional environments for the building model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function threeDimentionalEnvironment()
    {
        return $this->hasMany(ThreeDimentionalEnvironment::class);
    }

    public function building()
    {
        return $this->hasOne(Building::class);
    }
}
