<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'name',
        'phone',
        'company_name',
        'industry_type',
        'status',
        'is_suspended',
        'fcm_token',
        'auth_token',
        'profile_image',
        'description',
        'address',
        'latitude',
        'longitude',
    ];

    public function requests()
    {
        return $this->hasMany(request::class, 'user_id');
    }
}
