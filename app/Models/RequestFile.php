<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'relatable_id',
        'file',
        'user_id',
        'admin_id',
        'type',
    ];
}
