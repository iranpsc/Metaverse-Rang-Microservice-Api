<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    protected $casts = [
        'birthdate' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function errors()
    {
        return $this->morphMany(KycError::class, 'errorable');
    }

    protected function birthdate(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $value = \Morilog\Jalali\CalendarUtils::convertNumbers($value, true);
                $value = str_replace('/', '-', $value);
                $value = Carbon::parse($value)->format('Y-m-d');
                return \Morilog\Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d', $value)
                ->format('Y-m-d');
            }
        );
    }
}
