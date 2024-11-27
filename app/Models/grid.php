<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class grid extends Model
{
    use HasFactory;
    protected $fillable = [
        'warehouse_id',
        'grid_code',
        'size',
        'has_rack',
        'rack_multiplier',
        'status',
        'type',
        'is_occupied'
    ];

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class, 'warehouse_id');
    }
    public function adminActivities()
    {
        return $this->hasMany(adminactivity::class, 'retated_table_id');
    }
}
