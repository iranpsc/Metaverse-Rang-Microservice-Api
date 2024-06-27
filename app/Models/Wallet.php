<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $attributes = [
        'effect' => 0,
    ];

    /**
     * Get the user that owns the wallet.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format a number to a string representation with a suffix.
     *
     * @param int|float $number The number to format.
     * @return string The formatted number with a suffix.
     */
    public function format_number($number): string
    {
        if ($number >= 1000 && $number < 1000000) {
            $number = number_format($number / 1000, ($number * 1000) % 1000 > 0 ? 3 : 0);
            return $number . 'K';
        } elseif ($number >= 1000000 && $number < 1000000000) {
            $number = number_format($number / 1000000, ($number * 1000000) % 1000000 > 0 ? 3 : 0);
            return $number . 'M';
        } elseif ($number < 1000) {
            $number = number_format($number, ($number * 1000) % 1000 > 0 ? 3 : 0);
            return $number;
        }
    }
}
