<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Commision;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Morilog\Jalali\Jalalian;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_id',
        'buyer_id',
        'seller_id',
        'psc_amount',
        'irr_amount',
        'date',
    ];

    public function comission() {
        return $this->hasOne(Commision::class);
    }

    public function feature() {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    public function buyer() {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller() {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function scopeLatestFeatureTrades($query, Feature $feature) {
        return $query->where('feature_id', $feature->id)->get();
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'payable');
    }

    protected function createdAt():Attribute
    {
        return Attribute::make(
            get: fn ($value) => Jalalian::forge($value)->format('Y/m/d')
        );
    }
}
