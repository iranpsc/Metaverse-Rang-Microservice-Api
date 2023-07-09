<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function likes()
    {
        return $this->morphMany(Interaction::class, 'likeable')->where('liked', 1);
    }

    public function dislikes()
    {
        return $this->morphMany(Interaction::class, 'likeable')->where('liked', 0);
    }

    public function interactions()
    {
        return $this->morphMany(Interaction::class, 'likeable');
    }

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function incrementViews()
    {
        $this->views()->create(['ip_address' => request()->ip()]);
    }

    public function scopeCurrentEvents($query)
    {
        return $query->where('is_version', 0)
            ->whereDate('ends_at', '>', now());
    }

    public function scopeVersionEvents($query)
    {
        return $query->where('is_version', 1);
    }
}
