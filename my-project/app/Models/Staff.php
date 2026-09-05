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
        'phone',
        'profile_picture',
        'permissions',
        'password_hash',
        'role',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    protected static function booted(): void
        {
            static::saving(function ($model) {
                if ($model->isDirty('email')) {
                    $model->email_hash = \App\Helpers\HashHelper::email($model->email);
                }
            });
        }
}