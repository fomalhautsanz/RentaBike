<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table      = 'staff';
    protected $primaryKey = 'staff_id';
    public    $timestamps = false;

    protected $fillable = [
        'admin_id',
        'username',
        'full_name',
        'email',
        'password_hash',
        'role',
        'status',
    ];
}