<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class item extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'name',
        'user_id',
        'type',
        'request_quatity',
        'recived_quatity'
    ];
}
