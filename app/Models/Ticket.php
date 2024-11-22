<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array[]
     */
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'reciever_id',
        'attachment',
        'status',
        'department',
        'importance',
        'code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array[]
     */
    protected $attributes = [
        'status' => 0,
    ];

    public const NEW = 0;
    public const ANSWERED = 1;
    public const RESOLVED = 2;
    public const UNRESOLVED = 3;
    public const TRACKING = 4;
    public const CLOSED = 5;

    /**
     * Get the sender of the ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the receiver of the ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reciever()
    {
        return $this->belongsTo(User::class, 'reciever_id', 'id');
    }

    /**
     * Get the responses for the ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function responses()
    {
        return $this->hasMany(TicketResponse::class);
    }

    /**
     * Check if the ticket is closed.
     *
     * @return bool
     */
    public function isClosed()
    {
        return $this->status === static::CLOSED;
    }

    /**
     * Check if the ticket is open.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->status !== static::CLOSED;
    }

    /**
     * Close the ticket.
     *
     * @return void
     */
    public function close()
    {
        $this->update(['status' => static::CLOSED]);
    }

    /**
     * Mark the ticket as resolved.
     *
     * @return void
     */
    public function markAsResolved()
    {
        $this->update(['status' => static::RESOLVED]);
    }

    /**
     * Mark the ticket as unresolved.
     *
     * @return void
     */
    public function markAsUnresolved()
    {
        $this->update(['status' => static::UNRESOLVED]);
    }

    /**
     * Get the department attribute.
     *
     * @param $value
     * @return string|null
     */
    public function getDepartmentTitleAttribute($value)
    {
        return match ($value) {
            'technical_support' => 'پشتیبانی فنی',
            'citizens_safety' => 'امنیت شهروندان',
            'investment' => 'سرمایه گذاری',
            'inspection' => 'بازرسی',
            'protection' => 'حراست',
            'ztb' => 'مدیریت کل ز ت ب',
            null => null,
        };
    }
}
