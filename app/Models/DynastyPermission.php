<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DynastyPermission extends Model
{
    use HasFactory;

    protected $table = 'dynasty_permissions';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
