<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kyc extends Model
{
    use HasFactory;

    protected $fillable = [
        'shaba',
        'bank',
        'melli_card',
        'prove_picture',
        'resume',
        'fname',
        'lname',
        'father_name',
        'melli_code',
        'birthdate',
        'phone',
        'email',
        'province',
        'city',
        'number',
        'postal_code',
        'address',
        'site',
        'status',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function errors()
    {
        return $this->morphMany(KycError::class, 'errorable');
    }
}
