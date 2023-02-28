<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $attributes = [
        'status' => 0
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function newUniqueId()
    {
        return (string) $this->generateId();
    }

    private function generateId(): string
    {
        $id = 'TR-' . random_int(100000000, 999999999);
        while (self::where('id', $id)->exists()) {
            $id = 'TR-' . random_int(100000000, 999999999);
        }
        return $id;
    }

    protected function status(): Attribute {
        return Attribute::make(
            get: function($value) {
                return match($value) {
                    1 => 'موفق',
                    0 => 'معلق',
                    -1 => 'ناموفق',
                };
            }
        );
    }

    protected function asset(): Attribute
    {
        return Attribute::make(
            get: function($value) {
                return match($value) {
                    'blue' => 'رنگ آبی',
                    'yellow' => 'رنگ زرد',
                    'green' => 'رنگ سبز',
                    'psc' => 'PSC',
                    'irr' => 'ریال',
                };
            }
        );
    }

    protected function action() : Attribute
    {
        return Attribute::make(
            get: fn($value) => $value === 'withdraw' ? 'برداشت' : 'واریز'
        );
    }

    public function getTitle()
    {
        if ($this->payable instanceof BuyFeatureRequest) {
            return 'پیشنهاد خرید ملک';
        } elseif ($this->payable instanceof Trade) {
            return 'معامله ملک';
        } elseif ($this->payable instanceof Order) {
            return 'خرید دارایی';
        }
    }
}
