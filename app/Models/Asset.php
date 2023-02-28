<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function format_number($number): string
    {
        if ($number >= 1000 && $number < 1000000) {
            if (($number * 1000) % 1000 > 0) {
                $number = number_format($number / 1000, 3);
            } else {
                $number = number_format($number / 1000);
            }
            return $number . 'K';
        } elseif ($number >= 1000000 && $number < 1000000000) {
            if (($number * 1000000) % 1000000 > 0) {
                $number = number_format($number / 1000000, 3);
            } else {
                $number = number_format($number / 1000000);
            }
            return $number . 'M';
        } elseif ($number < 1000) {
            if (($number * 1000) % 1000 > 0) {
                $number = number_format($number, 3);
            } else {
                $number = number_format($number);
            }
            return $number;
        }
    }
}
