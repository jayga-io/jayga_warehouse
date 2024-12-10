<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class request extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'warehouseType_id',
        'status',
        'size',
        'start_date',
        'end_date'
    ];

    public function warehouseType()
    {
        return $this->belongsTo(warehouse_type::class, 'warehouseType_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(item::class, 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
