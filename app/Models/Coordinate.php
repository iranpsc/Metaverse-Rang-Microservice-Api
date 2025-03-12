<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, string>
     */
	protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected function casts()
    {
        return [
            'x' => 'decimal:12',
            'y' => 'decimal:12',
        ];
    }

    /**
     * The geometry that belong to the coordinate.
     *
     * @return BelongsTo
     */
	public function geometry()
	{
		return $this->belongsTo(Geometry::class, 'geometry_id', 'id');
	}

    /**
     * Implode the x and y coordinates into a string.
     *
     * @return string
     */
    public function implodeXY() {
        return implode(',', [$this->x, $this->y]);
    }
}
