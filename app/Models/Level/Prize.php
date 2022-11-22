<?php

namespace App\Models\Level;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'level_id',
        'psc',
        'blue',
        'red',
        'yellow',
        'union_license',
        'union_members_count',
        'observing_license',
        'gate_license',
        'lawyer_license',
        'city_counsil_entry',
        'special_residence_property',
        'property_on_area',
        'judge_entry',
        'satisfaction',
        'effect'
    ];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
