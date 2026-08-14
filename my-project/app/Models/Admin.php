<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'admin';
    protected $primaryKey = 'admin_id';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Tell Laravel to use password_hash instead of password
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}