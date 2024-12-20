<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class warehouse_type extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_name',
        'description',
        'admin_id'
    ];

    // Define the relationship with Admin
    public function admin()
    {
        return $this->belongsTo(admin::class, 'admin_id');
    }

    public function updates()
    {
        return $this->hasMany(updatewarehousetype::class, 'warehousetypes_id');
    }
}
