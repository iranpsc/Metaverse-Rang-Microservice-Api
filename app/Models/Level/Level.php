<?php

namespace App\Models\Level;

use App\Models\Image;
use App\Models\Level\Prize;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'score',
        'laravel_through_key'
    ];

    public function prize()
    {
        return $this->hasOne(Prize::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
