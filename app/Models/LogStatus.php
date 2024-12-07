<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'response',
        'api_name',
        'message'
    ];
}
