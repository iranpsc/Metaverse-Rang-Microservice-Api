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
        'status',
        'errors'
    ];

    protected $casts = [
        'errors' => 'array'
    ];

    public function bankable()
    {
        return $this->morphTo();
    }

    public function rejected(): bool
    {
        return $this->status === -1;
    }

    public function verified(): bool
    {
        return $this->status === 1;
    }

    public function pending(): bool
    {
        return $this->status === 0;
    }
}
