<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mag-gamit ta og model kay kinahanglan gyud siya para makakuha ta
 * og data gikan sa database gamit ang klaro nga PHP command imbes
 * literal SQL. Mas sayon ra pud siya basahon, tapos mao ni ang
 * gitawag sa controller (pananglitan `DashboardController`) para
 * makakuha og real data gikan sa mga table, gets?
 */

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_type',
        'action',
        'details',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

     /**
     * Get the name of the person who did this action.
     * We check user_type first (admin or staff) then look up their name manually,
     * since this isn't a normal foreign key relationship.
     */
     // ^ ah ok
    public function getActorNameAttribute(): string
    {
        return match ($this->user_type) {
            'admin'  => Admin::find($this->user_id)?->full_name ?? 'Unknown Admin',
            'staff'  => Staff::find($this->user_id)?->full_name ?? 'Unknown Staff',
            default  => 'System',
        };
    }
}