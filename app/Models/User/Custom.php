<?php

namespace App\Models\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Custom extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_code',
        'occupation',
        'education',
        'memory',
        'loved_city',
        'loved_country',
        'loved_language',
        'problem_solving',
        'prediction',
        'about'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function passions()
    {
        return $this->hasOne(Passion::class);
    }
}
