<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'response',
        'attachment',
        'ticket_id',
        'responser_name',
        'responser_id',
    ];

    public function ticket() {
        return $this->belongsTo(Ticket::class);
    }
}
