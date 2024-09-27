<?php

namespace App\Models\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalInfo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'occupation',
        'education',
        'memory',
        'loved_city',
        'loved_country',
        'loved_language',
        'problem_solving',
        'prediction',
        'about',
        'passions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'passions' => 'array',
    ];

    /**
     * The attributes with default values.
     *
     * @var array
     */
    protected $attributes = [
        'passions' => [
            'music' => false,
            'sport_health' => false,
            'art' => false,
            'language_culture' => false,
            'philosophy' => false,
            'animals_nature' => false,
            'aliens' => false,
            'food_cooking' => false,
            'travel_leature' => false,
            'manufacturing' => false,
            'science_technology' => false,
            'space_time' => false,
            'history' => false,
            'politics_economy' => false,
        ],
    ];

    /**
     * Get the user that owns the Custom
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
