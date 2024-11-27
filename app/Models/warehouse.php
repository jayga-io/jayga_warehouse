<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class warehouse extends Model
{
    use HasFactory;
    protected $fillable = [
        'location',
        'latitude',
        'longitude',
        'size',
        'contact_person',
        'contact_phone',
        'owner_name',
        'owner_phone',
        'owner_email',
        'total_grids',
        'grid_price_per_day',
        'status',
        'district',
        'area',
        'is_active',
        'warehouse_image',
        'warehouse_type_id',
        'admin_id',
        'description'
    ];

    public function warehouseType()
    {
        return $this->belongsTo(warehouse_type::class, 'warehouse_type_id');
    }

    public function admin()
    {
        return $this->belongsTo(admin::class, 'admin_id');
    }

    public function adminActivities()
    {
        return $this->hasMany(adminactivity::class, 'retated_table_id');
    }

    public function grids()
    {
        return $this->hasMany(grid::class, 'warehouse_id');
    }
}
