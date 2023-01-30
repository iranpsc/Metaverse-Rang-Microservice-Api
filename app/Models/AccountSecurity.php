<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountSecurity extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    protected $attributes = [
        'unlocked' => false,
        'until' => null,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function otp()
    {
        return $this->morphOne(Otp::class, 'verifiable');
    }
}
