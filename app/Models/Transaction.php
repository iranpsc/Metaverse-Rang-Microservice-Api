<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'payable_id',
        'payable_type',
        'asset',
        'amount',
        'action',
        'status'
    ];

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
}
