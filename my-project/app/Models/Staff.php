<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Staff extends Authenticatable
{
    protected $table      = 'staff';
    protected $primaryKey = 'staff_id';
    public    $timestamps = false;

    protected $fillable = [
        'admin_id',
        'username',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'profile_picture',
        'permissions',
        'password_hash',
        'role',
        'status',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'portal_remember_token',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Staff with role "Admin" and an active status may use the Admin Portal.
     */
    public function hasAdminPortalAccess(): bool
    {
        return strcasecmp((string) $this->role, 'Admin') === 0
            && strtolower((string) $this->status) === 'active';
    }

    // Case-insensitive, para mo-match bisan "view inventory" ang na-save
    public function hasPermission(string $permission): bool
    {
        $wanted = strtolower(trim($permission));

        foreach ((array) ($this->permissions ?? []) as $granted) {
            if (is_string($granted) && strtolower(trim($granted)) === $wanted) {
                return true;
            }
        }

        return false;
    }

    public function scopeAdminPortalEligible($query)
    {
        return $query->where('role', 'Admin')->where('status', 'active');
    }

    // Authenticate against password_hash instead of a "password" column.
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    protected static function booted(): void
        {
            static::saving(function ($model) {
                if ($model->isDirty('email')) {
                    $model->email_hash = \App\Helpers\HashHelper::email($model->email);
                }
            });
        }
}
