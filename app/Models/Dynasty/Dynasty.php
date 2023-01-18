<?php

namespace App\Models\Dynasty;

use App\Models\Feature;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dynasty extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'feature_id',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne
     */
    public function family(): HasOne
    {
        return $this->hasOne(Family::class);
    }

    /**
     * @return HasOne
     */
    public function feature(): HasOne
    {
        return $this->hasOne(Feature::class,'id','feature_id');
    }

    public function otp()
    {
        return $this->morphOne(Otp::class, 'verifiable');
    }
}
