<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Video extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getImageUrlAttribute()
    {
        return config('app.admin_panel_url') . '/uploads/' . $this->image;
    }

    public function getVideoUrlAttribute()
    {
        return config('app.admin_panel_url') . '/uploads/' . $this->fileName;
    }

    public function incrementViews()
    {
        $this->views()->updateOrCreate(
            ['ip_address' => request()->ip()],
            ['ip_address' => request()->ip()]
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

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(CommentReport::class, 'commentable');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(VideoSubCategory::class, 'video_sub_category_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_code', 'code');
    }
}
