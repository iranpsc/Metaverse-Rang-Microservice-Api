<?php

namespace App\Models\Level;

use App\Models\Image;
use App\Models\Level\Prize;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Level extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'score' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'laravel_through_key'
    ];

    /**
     * Get the prize associated with the level.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function prize()
    {
        return $this->hasOne(Prize::class);
    }

    /**
     * Get the image associated with the level.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    /**
     * Get the score percentage required to reach the next level for a given user.
     *
     * @param \App\Models\User $user
     * @return int
     */
    public function getScorePercentageToNextLevel(User $user): int
    {
        $nextLevel = Level::where('score', '>', $this->score)->orderBy('score')->first();
        return $nextLevel ? ($user->score * 100) / $nextLevel->score : 0;
    }

    /**
     * Get the general information associated with the level.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function generalInfo(): HasOne
    {
        return $this->hasOne(LevelGeneralInfo::class);
    }

    /**
     * Get the gem associated with the level.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function gem(): HasOne
    {
        return $this->hasOne(LevelGem::class);
    }

    /**
     * Get the licenses associated with the level.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function licenses(): HasOne
    {
        return $this->hasOne(LevelLicense::class);
    }

    /**
     * Get the gift associated with the level.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function gift(): HasOne
    {
        return $this->hasOne(LevelGift::class);
    }

    /**
     * Get the prizes associated with the level.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function prizes(): HasOne
    {
        return $this->hasOne(LevelPrize::class);
    }
}
