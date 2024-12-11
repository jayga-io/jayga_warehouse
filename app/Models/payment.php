<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'relatable_id',
        'type',
        'payment_region',
        'status',
        'amount'
    ];
}
