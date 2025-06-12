<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content', 'parent_id'];

    protected $withCount = ['likes', 'dislikes', 'replies'];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'likeable');
    }

    public function likes()
    {
        return $this->interactions()->where('liked', true);
    }

    public function dislikes()
    {
        return $this->interactions()->where('liked', false);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function isReply()
    {
        return !is_null($this->parent_id);
    }

    public function isParent()
    {
        return is_null($this->parent_id);
    }
}
