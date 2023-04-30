<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content'];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'likeable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
