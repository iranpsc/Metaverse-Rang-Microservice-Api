<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Video extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function incrementViews()
    {
        $this->views()->updateOrCreate(
            ['ip_address' => request()->ip()],
            ['ip_address' => request()->ip()]
        );
    }

    protected function fileName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config('rgb.admin_panel_url') . $value
        );
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config('rgb.admin_panel_url') . $value
        );
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'likeable');
    }

    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function categoriable()
    {
        return $this->morphTo();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(CommentReport::class, 'commentable');
    }
}
