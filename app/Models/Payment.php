<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ref_id',
        'card_pan',
        'gate_way',
        'product',
        'amount',
        'status',
    ];


}
