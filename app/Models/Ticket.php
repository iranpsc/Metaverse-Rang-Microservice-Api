<?php

namespace App\Models;

use App\Constants\TicketStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

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
        'responser_name'
    ];

    protected $attributes = [
        'status' => 0,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sender() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function reciever() {
        return $this->belongsTo(User::class, 'reciever_id', 'id');
    }

    public function responses() {
        return $this->hasMany(TicketResponse::class);
    }

    public function isClosed()
    {
        return $this->status === TicketStatus::CLOSED;
    }

    public function close()
    {
        $this->update(['status' => TicketStatus::CLOSED]);
    }

    public function markAsResolved()
    {
        $this->update(['status' => TicketStatus::RESOLVED]);
    }
    public function markAsUnresolved()
    {
        $this->update(['status' => TicketStatus::UNRESOLVED]);
    }

    protected function attachment(): Attribute {
        return Attribute::make(
            set: fn($value) => config('rgb.ftp-endpoint').$value
        );
    }

}
