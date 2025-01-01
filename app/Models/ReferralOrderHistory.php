<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralOrderHistory extends Model
{
    use HasFactory;

    protected $fillable = ['reference_id', 'referrer_id', 'amount'];
}
