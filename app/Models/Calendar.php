<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    public function likes() {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function dislikes() {
        return $this->morphMany(Dislike::class, 'dislikeable');
    }

    public function image() {
        return $this->morphOne(Image::class, 'imageable');
    }
}
