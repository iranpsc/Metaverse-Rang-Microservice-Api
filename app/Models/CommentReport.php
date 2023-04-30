<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comment_id',
        'content'
    ];

    protected $attribute = [
        'status' => 0,
    ];
}
