<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class updatewarehousetype extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehousetypes_id',
        'admin_id'
    ];

    public function admin()
    {
        return $this->belongsTo(admin::class, 'admin_id');
    }

    public function warehouseType()
    {
        return $this->belongsTo(warehouse_type::class, 'warehousetypes_id');
    }
}
