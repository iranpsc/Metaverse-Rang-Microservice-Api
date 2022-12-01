<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'value',
        'verified',
    ];

    protected $attributes = [
        'verified' => false,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeResetInfo($query, $user, $type)
    {
        return $query->whereUserId($user->id)->whereType($type)->whereVerified(true);
    }

    public function otp()
    {
        return $this->morphOne(Otp::class, 'verifiable');
    }

}
