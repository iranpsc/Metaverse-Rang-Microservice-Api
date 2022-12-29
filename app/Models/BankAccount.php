<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'shaba_num',
        'card_num',
        'status'
    ];

    public function bankable()
    {
        return $this->morphTo();
    }

    public function verified()
    {
        return $this->status === 1;
    }

    public function errors()
    {
        return $this->morphMany(KycError::class, 'errorable');
    }
}
