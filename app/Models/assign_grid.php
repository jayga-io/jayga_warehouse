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
        'request_id'
    ];

    public function item()
    {
        return $this->belongsTo(item::class, 'item_id');
    }

    public function grid()
    {
        return $this->belongsTo(grid::class, 'grid_id');
    }
}
