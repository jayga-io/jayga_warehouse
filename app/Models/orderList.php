<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class orderList extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'payment',
        'payment_status',
        'payment_type',
        'start_date',
        'end_date'
    ];
}
