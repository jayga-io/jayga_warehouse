<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'fcm_token',
        'auth_token',
        'status',
        'role',
        'admin_image',
    ];

    // Define the relationship with WarehouseType
    public function warehouseTypes()
    {
        return $this->hasMany(warehouse_type::class);
    }
}
