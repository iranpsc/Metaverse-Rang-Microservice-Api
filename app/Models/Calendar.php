<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    public function interactions() {
        return $this->morphMany(Interaction::class, 'likeable');
    }

    public function image() {
        return $this->morphOne(Image::class, 'imageable');
    }
}
