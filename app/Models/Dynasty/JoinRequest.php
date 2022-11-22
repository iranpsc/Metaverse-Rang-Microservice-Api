<?php

namespace App\Models\Dynasty;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JoinRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_user',
        'to_user',
        'status',
        'relation',
        'no_father',
        'death_license',
        'mother_code',
    ];

    /**
     * @return BelongsTo
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class,'from_user', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class,'to_user', 'id');
    }

    public function scopelatestSentJoinRequest($query, $from_user, $to_user)
    {
        return $query->where('from_user', $from_user)->where('to_user', $to_user)->latest()->first();
    }

    public function otp()
    {
        return $this->morphOne(Otp::class, 'verifiable');
    }
}
