<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kyc extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'melli_card',
        'fname',
        'lname',
        'melli_code',
        'province',
        'status',
        'user_id',
        'errors',
        'verify_text_id',
        'video',
        'birthdate',
        'gender'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birthdate' => 'date',
        'errors' => 'array'
    ];

    /**
     * The attributes with default values.
     *
     * @var array<string>
     */
    protected $attributes = [
        'status' => 0,
    ];

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->fname . ' ' . $this->lname;
    }

    /**
     * Get the user that owns the kyc.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether the kyc is rejected.
     *
     * @return bool
     */
    public function rejected(): bool
    {
        return $this->status === -1;
    }

    /**
     * Check if the KYC status is approved.
     *
     * @return bool True if the status is approved, false otherwise.
     */
    public function approved(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if the KYC status is pending.
     *
     * @return bool True if the status is pending, false otherwise.
     */
    public function pending(): bool
    {
        return $this->status === 0;
    }
}
