<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset', 'amount', 'user_id', 'status',
    ];

    public function transaction() {
	    return $this->morphOne(Transaction::class , 'payable');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function getTitle()
    {
        return match($this->asset) {
            'yellow' => 'رنگ زرد',
            'blue' => 'رنگ آبی',
            'red' => 'رنگ قرمز',
            'psc' => 'psc',
            'irr' => 'ریال',
        };
    }
}
