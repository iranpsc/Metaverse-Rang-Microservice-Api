<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Get all of the likes for the calendar event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function likes()
    {
        return $this->morphMany(Interaction::class, 'likeable')->where('liked', 1);
    }

    /**
     * Get all of the dislikes for the calendar event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function dislikes()
    {
        return $this->morphMany(Interaction::class, 'likeable')->where('liked', 0);
    }

    /**
     * Get all of the interactions for the calendar event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function interactions()
    {
        return $this->morphMany(Interaction::class, 'likeable');
    }

    /**
     * Get all of the views for the calendar event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    /**
     * Increment the view count for the calendar event.
     *
     * @return void
     */
    public function incrementViews()
    {
        $this->views()->create(['ip_address' => request()->ip()]);
    }

    /**
     * Scope a query to only include events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEvents($query)
    {
        return $query->where('is_version', 0)->orderBy('starts_at', 'desc');
    }

    /**
     * Scope a query to only include versions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVersions($query)
    {
        return $query->where('is_version', 1)->orderBy('created_at', 'desc');
    }
}
