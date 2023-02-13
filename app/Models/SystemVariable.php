<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemVariable extends Model
{
    use HasFactory;

    protected $casts = [
        'slug' => 'string',
        'value' => 'int'
    ];

    public function scopeGetByKey($query, $key):int
    {
        return $query->where('slug', $key)->pluck('value')->first();
    }
}
