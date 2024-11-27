<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class adminactivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'retated_table_id',
        'description',
        'admin_id',
        'type'
    ];

    public function admin()
    {
        return $this->belongsTo(admin::class, 'admin_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class, 'retated_table_id');
    }
}
