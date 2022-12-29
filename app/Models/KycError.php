<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycError extends Model
{
    use HasFactory;

    public function errorable()
    {
        $this->morphTo();
    }
}
