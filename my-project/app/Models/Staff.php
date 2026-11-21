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
     * Staff with an admin-level role and an active status may use the Admin Portal.
     */
    public function hasAdminPortalAccess(): bool
    {
        return in_array(strtolower((string) $this->role), ['admin', 'super admin'], true)
            && strtolower((string) $this->status) === 'active';
    }

    public function isSuperAdmin(): bool
    {
        return strcasecmp((string) $this->role, 'Super Admin') === 0;
    }

    // Case-insensitive, para mo-match bisan "view inventory" ang na-save
    public function hasPermission(string $permission): bool
    {
        $wanted = strtolower(trim($permission));
        if ($this->isSuperAdmin()) {
            return true;
        }

        $grantedPermissions = [];

        foreach ((array) ($this->permissions ?? []) as $granted) {
            if (is_string($granted)) {
                $grantedPermissions[] = strtolower(trim($granted));
            }
        }

        if (in_array($wanted, $grantedPermissions, true)) {
            return true;
        }

        return $wanted === 'view inventory'
            && (bool) array_intersect(
                ['add inventory', 'edit inventory', 'delete inventory'],
                $grantedPermissions
            );
    }

    public function scopeAdminPortalEligible($query)
    {
        return $query->whereIn('role', ['Admin', 'Super Admin'])->where('status', 'active');
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
