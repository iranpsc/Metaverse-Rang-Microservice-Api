<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinate extends Model
{
	protected $fillable = [
		'geometry_id',
		'x',
		'y',
	];

    protected $casts = [
        'x' => 'double',
        'y' => 'double',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

	public function geometry()
	{
		return $this->belongsTo(Geometry::class, 'geometry_id', 'id');
	}

    public function implodeXY() {
        return implode(',', [$this->x, $this->y]);
    }
}
