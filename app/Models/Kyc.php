<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kyc extends Model
{
    use HasFactory;

    protected $fillable = [
        'melli_card',
        'fname',
        'lname',
        'melli_code',
        'province',
        'status',
        'user_id',
        'errors',
        'verify_text',
        'video',
        'birthdate'
    ];

    protected $casts = [
        'birthdate' => 'datetime',
        'errors' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rejected(): bool
    {
        return $this->status === -1;
    }

    public function approved(): bool
    {
        return $this->status === 1;
    }

    public function pending(): bool
    {
        return $this->status === 0;
    }
}
