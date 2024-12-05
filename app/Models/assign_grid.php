<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class assign_grid extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'grid_id',
        'quantity',
        'order_list_id'
    ];
}
