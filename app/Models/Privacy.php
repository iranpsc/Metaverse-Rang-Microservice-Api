<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Privacy extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'display' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
